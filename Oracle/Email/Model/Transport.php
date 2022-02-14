<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Email\Model;

use Magento\Framework\Mail\EmailMessageInterface;
use Magento\Framework\Mail\MimeMessage;
use Zend\Mail\Message;
use Zend\Mail\Address;
use Zend\Mail\AddressList;
use Zend\Mime\Mime;

class Transport extends \Zend\Mail\Transport\Sendmail implements \Magento\Framework\Mail\TransportInterface
{
    protected $_helper;

    /** @var \Magento\Framework\Mail\MessageInterface | \Magento\Framework\Mail\EmailMessageInterface $message */
    protected $_message;
    
    protected $_queueManager;
    protected $_source;
    protected $_platform;
    protected $_encoder;
    protected $_objects;

    /** @var \Oracle\M2\Core\Log\LoggerInterface $logger */
    protected $logger;

    /**
     * @param \Oracle\M2\Connector\QueueManagerInterface $queueManager
     * @param \Oracle\M2\Email\SettingsInterface $helper
     * @param \Oracle\M2\Connector\Event\PlatformInterface $platform
     * @param \Oracle\Email\Model\Event\Source $source
     * @param \Magento\Framework\Mail\MessageInterface | \Magento\Framework\Mail\EmailMessageInterface $message
     * @param \Oracle\M2\Core\DataObjectFactory $objects
     * @param \Oracle\M2\Common\Serialize\BiDirectional $encoder
     * @param \Magento\Framework\Mail\Template\SenderResolverInterface $senderResolver,
     * @param \Oracle\M2\Core\Log\LoggerInterface $logger
     */
    public function __construct(
        \Oracle\M2\Connector\QueueManagerInterface $queueManager,
        \Oracle\M2\Email\SettingsInterface $helper,
        \Oracle\M2\Connector\Event\PlatformInterface $platform,
        \Oracle\Email\Model\Event\Source $source,
        $message,
        \Oracle\M2\Core\DataObjectFactory $objects,
        \Oracle\M2\Common\Serialize\BiDirectional $encoder,
        \Magento\Framework\Mail\Template\SenderResolverInterface $senderResolver,
        \Oracle\M2\Core\Log\LoggerInterface $logger
    ) {
        $this->_helper = $helper;
        $this->_message = $message;
        $this->_queueManager = $queueManager;
        $this->_source = $source;
        $this->_platform = $platform;
        $this->_encoder = $encoder;
        $this->_objects = $objects;
        $this->senderResolver = $senderResolver;
        $this->logger = $logger;
    }

    /**
     * @see parent
     */
    public function sendMessage()
    {
        $storeId = $this->_helper->getStoreId($this->_message);
        if (is_a($this->_message, '\Magento\Framework\Mail\MailMessageInterface')
            || is_a($this->_message, '\Magento\Framework\Mail\EmailMessageInterface')
        ) {
            /** @var \Magento\Framework\Mail\EmailMessageInterface $message */
            $message = null;
            $body = null;
            if (is_a($this->_message, '\Magento\Framework\Mail\EmailMessageInterface')) {
                /** @var \Magento\Framework\Mail\EmailMessageInterface $message */
                $message = $this->_message;
                $body = quoted_printable_decode($message->getBodyText());
            } else {
                /** @var \Magento\Framework\Mail\MailMessageInterface $message */
                $message = Message::fromString($this->_message->getRawMessage());
                $body = $message->getBodyText();
            }
            $headers = $message->getHeaders();
            $fromArray = $this->getEmailsFromAddressList($message->getFrom());
            $from = null;
            if (count($fromArray)) {
            	$from = array_pop($fromArray);
            } else {
            	// Falls back to general email address. see https://github.com/magento/magento2/issues/14952
            	$from = $this->senderResolver->resolve('general', $storeId);
            	$this->logger->debug(
            	    "Fell back to general email address for 'From'."
                    . " See https://github.com/magento/magento2/issues/14952"
                );
            }

            $recipients = array_merge(
                $this->getEmailsFromAddressList($message->getTo()),
                $this->getEmailsFromAddressList($message->getCc()),
                $this->getEmailsFromAddressList($message->getBcc())
            );
        } else {
            $body = $this->_message->getBody()->getRawContent();
            $headers = $this->_message->getHeaders();
            $from = $this->_message->getFrom();
            $recipients = $this->_message->getRecipients();
        }
        if ($body !== 'nosend') {

            $this->logger->debug('Generating message delivery for ' . implode(', ', $recipients)
                . "\nBacktrace: \n\t" . $this->logger->getSimplifiedBacktrace());

            $action = $this->_source->action($this->_message);
            if (!empty($action)) {
                $container = $this->_encoder->decode($body);
                $message = $this->_objects->create([
                    'data' => [
                        'delivery' => $container['delivery'],
                        'headers' => $headers,
                        'from' => $from,
                    ]
                ]);
                $event = $this->_platform->annotate($this->_source, $message, $action, $storeId, $container['context']);
                foreach ($recipients as $email) {
                    $event['data']['delivery']['recipients'] = [
                        [
                            'deliveryType' => 'selected',
                            'id' => $email,
                            'type' => 'contact'
                        ]
                    ];
                    if ($this->_helper->isSendingQueued('store', $storeId) || !$this->_platform->dispatch($event)) {
                        $this->_queueManager->save($event);
                    }
                }
            }
        }
    }

    /**
     * @see \Magento\Framework\Mail\TransportInterface::getMessage()
     */
    public function getMessage()
    {
        return $this->_message;
    }

    /**
     * Convenience function for pulling the email address string from an AddressList
     * @see \Zend\Mail\AddressList
     *
     * @param AddressList | Address[] $addressList
     * @return String[]
     */
    protected function getEmailsFromAddressList($addressList)
    {
        $emails = [];
        foreach ($addressList as $address) {
            $emails[] = $address->getEmail();
        }
        return $emails;
    }
}

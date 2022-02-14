<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Cart;

abstract class ExtensionAbstract extends \Oracle\M2\Connector\Discovery\ExtensionPushEventAbstract implements \Oracle\M2\Connector\Discovery\TransformEventInterface
{
    /** @var \Oracle\M2\Core\Sales\QuoteManagementInterface */
    protected $_quoteRepo;

    /**
     * @param \Oracle\M2\Core\Sales\QuoteManagementInterface $quoteRepo
     * @param \Oracle\M2\Core\Store\ManagerInterface $storeManager
     * @param \Oracle\M2\Connector\QueueManagerInterface $queueManager
     * @param \Oracle\M2\Connector\SettingsInterface $connectorSettings
     * @param \Oracle\M2\Connector\Event\HelperInterface $helper
     * @param \Oracle\M2\Connector\Event\PlatformInterface $platform
     * @param \Oracle\M2\Connector\Event\SourceInterface $source
     */
    public function __construct(
        \Oracle\M2\Core\Sales\QuoteManagementInterface $quoteRepo,
        \Oracle\M2\Core\Store\ManagerInterface $storeManager,
        \Oracle\M2\Connector\QueueManagerInterface $queueManager,
        \Oracle\M2\Connector\SettingsInterface $connectorSettings,
        \Oracle\M2\Cart\SettingsInterface $helper,
        \Oracle\M2\Connector\Event\PlatformInterface $platform,
        \Oracle\M2\Connector\Event\SourceInterface $source
    ) {
        parent::__construct(
            $storeManager,
            $queueManager,
            $connectorSettings,
            $helper,
            $platform,
            $source
        );
        $this->_quoteRepo = $quoteRepo;
    }

    /**
     * @see parent
     */
    public function transformEvent($observer)
    {
        $data = [];
        $transform = $observer->getTransform();
        $event = $transform->getContext();
        $quote = $this->_quoteRepo->getById($event['id']);
        if (empty($quote)) {
            $quote = new \Oracle\M2\Core\DataObject(
                [
                    'id' => $event['id'],
                    'items_count' => 0
                ]
            );
            $quote->setStore($this->_storeManager->getStore($event['storeId']));
            $quote->isDeleted(true);
        } else {
            $quote = clone $quote;
            $quote->isDeleted($event['is_deleted']);
        }
        $quote->isObjectNew($event['is_new']);
        if (array_key_exists('emailAddress', $event)) {
            $quote->setCustomerEmail($event['emailAddress']);
        }
        if (array_key_exists('redirect_url', $event)) {
            $quote->setRedirectUrl($event['redirect_url']);
        }
        $data = $this->_source->transform($quote);
        $transform->setCart($data);
    }

    /**
     * Adds an API dropdown to the integration extension
     *
     * @param mixed $observer
     * @return void
     */
    public function integrationAdditional($observer)
    {
        $observer->getEndpoint()->addFieldToExtension('cart_recovery', [
            'id' => 'selectors',
            'name' => 'Email CSS Selectors',
            'type' => 'text',
            'required' => true,
            'typeProperties' => [ 'default' => $this->_emailSelector() ]
        ]);

        $observer->getEndpoint()->addFieldToExtension(
            'cart_recovery',
            [
                'id' => 'tax_included',
                'name' => 'Include Tax with Price',
                'type' => 'boolean',
                'required' => true,
                'typeProperties' => ['default' => false]
            ]
        );
    }

    /**
     * @see parent
     */
    protected function _getObject($observer)
    {
        return $observer->getQuote();
    }

    /**
     * Default email selector for the platform
     *
     * @return string
     */
    protected function _emailSelector()
    {
        return '.validate-email';
    }
}

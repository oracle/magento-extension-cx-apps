<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Email\Plugin;

use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Sales\Model\Order\Email\Container\IdentityInterface;
use Magento\Sales\Model\Order\Email\Container\Template;

class OrderSenderBuilder extends \Magento\Sales\Model\Order\Email\SenderBuilder
{
    protected $_settings;

    /**
     * @see parent
     */
    public function __construct(
        \Oracle\M2\Email\SettingsInterface $settings,
        Template $templateContainer,
        IdentityInterface $identityContainer,
        TransportBuilder $transportBuilder
    ) {
        parent::__construct(
            $templateContainer,
            $identityContainer,
            $transportBuilder
        );
        $this->_settings = $settings;
    }

    /**
     * @see parent
     */
    public function send()
    {
        if ($this->_isForceMagento('bcc')) {
            $this->configureEmailTemplate();
            $this->transportBuilder->addTo(
                $this->identityContainer->getCustomerEmail(),
                $this->identityContainer->getCustomerName()
            );
            $transport = $this->transportBuilder->getTransport();
            $transport->sendMessage();
            $this->_splitSend($this->identityContainer->getEmailCopyTo());
        } else {
            parent::send();
        }
    }

    /**
     * @see parent
     */
    public function sendCopyTo()
    {
        if ($this->_isForceMagento('copy')) {
            $this->_splitSend($this->identityContainer->getEmailCopyTo());
        } else {
            parent::sendCopyTo();
        }
    }

    /**
     * Forces a Magento send on the separate emails
     *
     * @param string $copyMethod
     * @return boolean
     */
    protected function _isForceMagento($copyMethod)
    {
        $options = $this->templateContainer->getTemplateOptions();
        if ($this->identityContainer->getCopyMethod() == $copyMethod && isset($options['store'])) {
            $mappingId = $this->_settings->getLookup($this->templateContainer->getTemplateId(), 'store', $options['store']);
            if ($mappingId) {
                $data = $this->_helper->getMessage('mapping', $mappingId);
                if (!empty($data) && $data['enabled']) {
                    return $this->_settings->isForceMagento('store', $options['store']);
                }
            }
        }
        return false;
    }

    /**
     * Split the Magento within Oracle send
     *
     * @param array $copyTo
     */
    protected function _splitSend($copyTo)
    {
        $copyTo = $this->identityContainer->getEmailCopyTo();
        if (!empty($copyTo)) {
            foreach ($copyTo as $email) {
                $this->configureEmailTemplate();
                $variables = $this->templateContainer->getTemplateVars();
                $variables['forceMagento'] = true;
                $this->transportBuilder->setTemplateVars($variables);
                $this->transportBuilder->addTo($email);
                $transport = $this->transportBuilder->getTransport();
                $transport->sendMessage();
            }
        }
    }
}

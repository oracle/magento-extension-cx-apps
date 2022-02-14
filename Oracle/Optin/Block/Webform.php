<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Optin\Block;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\CustomerData\SectionSourceInterface;

class Webform extends \Magento\Customer\Block\Newsletter implements SectionSourceInterface
{
    private $_helper;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory
     * @param CustomerRepositoryInterface $customerRepository
     * @param AccountManagementInterface $customerAccountManagement
     * @param \Oracle\M2\Optin\SettingsInterface $helper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Oracle\M2\Optin\SettingsInterface $helper,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
        CustomerRepositoryInterface $customerRepository,
        AccountManagementInterface $customerAccountManagement,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $customerSession,
            $subscriberFactory,
            $customerRepository,
            $customerAccountManagement,
            $data
        );
        $this->_helper = $helper;
        if ($this->isFormEnabled()) {
            $this->setTemplate('Oracle_Optin::webform.phtml');
        } else {
            $this->setTemplate('Magento_Customer::form/newsletter.phtml');
        }
    }

    /**
     * Forwards context aware helper call
     *
     * @return boolean
     */
    public function isFormEnabled()
    {
        return $this->_helper->isFormEnabled('store', $this->_storeManager->getStore(true));
    }

    /**
     * Forwards context aware helper call
     *
     * @return string
     */
    public function getWebformUrl()
    {
        return $this->_helper->getWebformUrl($this->getCustomer()->getEmail(), 'store', $this->_storeManager->getStore(true));
    }

    /**
     * Forwards the context aware helper call
     *
     * @return mixed
     */
    public function getWebformHeight()
    {
        return $this->_helper->getWebformHeight('store', $this->_storeManager->getStore(true));
    }

    /**
     * View-Model (Block) data for KnockoutJS AJAX call
     *
     * @return array
     */
    public function getSectionData()
    {
        $webformData = [];
        $webformData['enabled'] = $this->isFormEnabled();
        if (!$webformData['enabled'] || !$this->customerSession->getCustomerId()) {
            return $webformData;
        }

        $webformData['webformBefore'] = $this->escapeHtml($this->getChildHtml('form_before'));
        $webformData['webformHeight'] = $this->getWebformHeight();
        $webformData['webformUrl'] = $this->escapeHtml($this->getWebformUrl());

        return $webformData;
    }
}

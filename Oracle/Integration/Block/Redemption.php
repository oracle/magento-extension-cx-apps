<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Integration\Block;

use Magento\Customer\CustomerData\SectionSourceInterface;

class Redemption extends \Magento\Framework\View\Element\Template implements SectionSourceInterface
{
    protected $_helper;
    protected $_checkoutSession;
    protected $_orderFactory;
    protected $_lastOrder;
    protected $_currentStore;
    protected $_settings;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Oracle\M2\Integration\CouponSettingsInterface $helper
     * @param \Oracle\M2\Connector\SettingsInterface $settings
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Oracle\M2\Integration\CouponSettingsInterface $helper,
        \Oracle\M2\Connector\SettingsInterface $settings,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_checkoutSession = $checkoutSession;
        $this->_orderFactory = $orderFactory;
        $this->_helper = $helper;
        $this->_settings = $settings;
        $this->_currentStore = $this->_storeManager->getStore(true);
    }

    /**
     * Is coupon redemption enabled?
     *
     * @return boolean
     */
    public function isEnabled()
    {
        return $this->_helper->isCouponEnabled('store', $this->_currentStore)
            && $this->getSiteId()
            && $this->getOrder()
            && $this->getOrder()->getCouponCode();
    }

    /**
     * Gets the Oracle site hash for communication
     *
     * @return string
     */
    public function getSiteId()
    {
        return $this->_settings->getSiteId('store', $this->_currentStore);
    }

    /**
     * Gets the last order that was placed
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        if (is_null($this->_lastOrder)) {
            $orderId = $this->_checkoutSession->getLastOrderId();
            if ($orderId) {
                $order = $this->_orderFactory->create()->load($orderId);
                if ($order && $order->getId()) {
                    $this->_lastOrder = $order;
                }
            }
        }
        return $this->_lastOrder;
    }

    /**
     * View-Model (Block) data for KnockoutJS AJAX call
     *
     * @return array
     */
    public function getSectionData()
    {
        $redemptionData = [];
        $redemptionData['enabled'] = $this->isEnabled();
        $order = $this->getOrder();
        if (!$redemptionData['enabled'] || !$order) {
            return $redemptionData;
        }
        $redemptionData['siteId'] = $this->escapeHtml($this->getSiteId());
        $redemptionData['email'] = $this->escapeHtml($order->getCustomerEmail());
        $redemptionData['coupon'] = $this->escapeHtml($order->getCouponCode());
        $redemptionData['orderId'] = $this->escapeHtml($order->getIncrementId());
        $redemptionData['orderSubtotal'] = $this->escapeHtml($order->getSubtotal());
        $redemptionData['orderDiscount'] = $this->escapeHtml($order->getDiscountAmount());

        return $redemptionData;
    }
}

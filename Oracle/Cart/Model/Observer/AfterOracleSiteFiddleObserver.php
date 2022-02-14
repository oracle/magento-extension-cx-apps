<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Cart\Model\Observer;

class AfterOracleSiteFiddleObserver extends ObserverAbstract
{
    const CART_FIDDLE = 'oracle_cart_fiddle';

    protected $_checkout;
    protected $_eventManager;
    protected $_settings;

    /**
     * @param \Oracle\M2\Cart\SettingsInterface $settings
     * @param \Oracle\M2\Core\Sales\CheckoutSessionInterface $checkout
     * @param \Oracle\M2\Core\Event\ManagerInterface $eventManager
     */
    public function __construct(
        \Oracle\Cart\Model\Observer $observer,
        \Oracle\M2\Cart\SettingsInterface $settings,
        \Oracle\M2\Core\Sales\CheckoutSessionInterface $checkout,
        \Oracle\M2\Core\Event\ManagerInterface $eventManager
    ) {
        parent::__construct($observer);
        $this->_checkout = $checkout;
        $this->_eventManager = $eventManager;
        $this->_settings = $settings;
    }

    /**
     * @see parent
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->_isEnabled()) {
            $this->_eventManager->dispatch(self::CART_FIDDLE, [
                'quote' => $this->_checkout->getQuote()->setUpdatedAt(date('c'))
            ]);
        }
    }

    /**
     * Runs through a variety of checks to determined if the event
     * should be forwarded
     *
     * @return boolean
     */
    protected function _isEnabled()
    {
        return (
            $this->_checkout->getQuoteId() &&
            $this->_checkout->getQuote()->getIsActive()
        );
    }
}

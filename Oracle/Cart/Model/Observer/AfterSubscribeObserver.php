<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Cart\Model\Observer;

/**
 * Class AfterSubscribeObserver
 * @package Oracle\Cart\Model\Observer
 */
class AfterSubscribeObserver extends AfterOracleSiteFiddleObserver
{
    /**
     * Queue up event changes and dispatch the cart fiddle event.
     * 
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $this->_observer->pushChanges($observer);
        
        if ($this->_isEnabled()) {
            $this->_eventManager->dispatch(self::CART_FIDDLE, [
                'quote' => $this->_checkout->getQuote()->setUpdatedAt(date('c'))
            ]);
        }
    }
}

<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Cart\Model\Observer;

class AfterTransformEventObserver implements \Magento\Framework\Event\ObserverInterface
{
    /** @var \Oracle\Cart\Model\Observer */
    protected $observer;

    /**
     * @param \Oracle\Cart\Model\Observer $observer
     */
    public function __construct(\Oracle\Cart\Model\Observer $observer) {
        $this->observer = $observer;
    }

    /**
     * Transforms the cart event to be flushed.
     *
     * Dispatches an order event if cart is in COMPLETE phase (Order ID has been reserved).
     * This is done in order to preserve the tid in the event that a 3rd party payment gateway
     * interrupts the checkout process
     *
     * @see \Magento\Framework\Event\ObserverInterface::execute
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $this->observer->transformEvent($observer);
    }
}

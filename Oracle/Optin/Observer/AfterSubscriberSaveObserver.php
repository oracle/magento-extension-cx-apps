<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Optin\Observer;

use Oracle\M2\Core\Sales\CheckoutSessionInterface;

class AfterSubscriberSaveObserver extends ObserverAbstract
{
    /** @var CheckoutSessionInterface  */
    protected $checkout;

    /** @var \Oracle\M2\Core\Event\ManagerInterface  */
    protected $eventManager;

    /**
     * @param \Oracle\Optin\Model\Observer
     */
    public function __construct(
        \Oracle\Optin\Model\Observer $observer,
        \Oracle\M2\Core\Sales\CheckoutSessionInterface $checkout,
        \Oracle\M2\Core\Event\ManagerInterface $eventManager
    ) {
        parent::__construct($observer);
        $this->checkout = $checkout;
        $this->eventManager = $eventManager;
    }

    /**
     * @see parent
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $this->_observer->pushChanges($observer);
        if ($this->isQuoteActive()) {
            /** @var \Magento\Newsletter\Model\Subscriber $subscriber */
            $subscriber = $observer->getSubscriber();
            $this->eventManager->dispatch(
                'oracle_optin_subscribe',
                [
                    'quote' => $this->checkout->getQuote()
                        ->setCustomerEmail($subscriber->getEmail())
                        ->setUpdatedAt(date('c'))
                ]
            );
        }
    }

    /**
     * @return bool
     */
    private function isQuoteActive()
    {
        return $this->checkout->getQuoteId() &&
        $this->checkout->getQuote()->getIsActive();
    }
}

<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Optin\Controller\Checkout;

class Index extends \Magento\Framework\App\Action\Action
{
    /** @var \Magento\Checkout\Model\Session */
    protected $_checkout;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Checkout\Model\Session $checkout
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkout
    ) {
        parent::__construct($context);
        $this->_checkout = $checkout;
    }

    /**
     * @see parent
     */
    public function execute()
    {
        $subscribed = (bool)$this->getRequest()->getParam('subscribed', false);
        if ($subscribed) {
            $this->_checkout->setSubscribeToNewsletter(true);
            if ($this->isQuoteActive()) {
                $this->_eventManager->dispatch('oracle_optin_subscribe', [
                    'quote' => $this->_checkout->getQuote()->setUpdatedAt(date('c'))
                ]);
            }
        } else {
            $this->_checkout->unsSubscribeToNewsletter();
        }
        $this->getResponse()->setHttpResponseCode(200);
    }

    /**
     * @return bool
     */
    private function isQuoteActive()
    {
        return $this->_checkout->getQuoteId() &&
        $this->_checkout->getQuote()->getIsActive();
    }
}

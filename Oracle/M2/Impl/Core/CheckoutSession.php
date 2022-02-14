<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Impl\Core;

class CheckoutSession implements \Oracle\M2\Core\Sales\CheckoutSessionInterface
{
    protected $_checkout;
    protected $_cart;

    /**
     * @param \Magento\Checkout\Model\Session $checkout
     * @param \Magento\Checkout\Model\Cart $cart
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkout,
        \Magento\Checkout\Model\Cart $cart
    ) {
        $this->_checkout = $checkout;
        $this->_cart = $cart;
    }

    /**
     * @see parent
     */
    public function getInitializedCart()
    {
        return $this->_cart;
    }

    /**
     * @see parent
     */
    public function getQuote()
    {
        return $this->_checkout->getQuote();
    }

    /**
     * @see parent
     */
    public function getQuoteId()
    {
        return $this->_checkout->getQuoteId();
    }

    /**
     * @see parent
     */
    public function resetCheckout()
    {
        $this->_checkout->resetCheckout();
        return $this;
    }
}

<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Core\Sales;

interface CheckoutSessionInterface
{
    /**
     * Gets a Magento cart already initialized and reset
     *
     * @return mixed
     */
    public function getInitializedCart();

    /**
     * Gets a cart from the session, or creates an empty one
     *
     * @return \Magento\Quote\Model\Quote
     */
    public function getQuote();

    /**
     * Attempts to pull a quoteId off the session
     *
     * @return mixed
     */
    public function getQuoteId();

    /**
     * Resets the the checkout session
     *
     * @return void
     */
    public function resetCheckout();
}

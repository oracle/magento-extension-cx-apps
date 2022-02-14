<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Core\Sales;

interface QuoteManagementInterface
{
    /**
     * Assign a cart to a customer in a given store
     *
     * @param mixed $quoteId
     * @param mixed $customerId
     * @param mixed $storeId
     * @return mixed
     */
    public function assignCustomer($quoteId, $customerId, $storeId);

    /**
     * Provided there's a customer ID, load an active cart
     *
     * @param mixed $customerId
     * @return mixed
     */
    public function getCartForCustomer($customerId);

    /**
     * Gets a cart by ID
     *
     * @param int $quoteId
     * @return \Magento\Quote\Api\Data\CartInterface
     */
    public function getById($quoteId);
}

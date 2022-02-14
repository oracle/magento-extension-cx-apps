<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Impl\Core;

class QuoteManager implements \Oracle\M2\Core\Sales\QuoteManagementInterface
{
    protected $_quoteManagement;
    protected $_quoteRepo;
    protected $_logger;

    /**
     * @param \Magento\Quote\Api\CartManagementInterface $quoteManagement
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepo
     * @param \Oracle\M2\Core\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Quote\Api\CartManagementInterface $quoteManagement,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepo,
        \Oracle\M2\Core\Log\LoggerInterface $logger
    ) {
        $this->_quoteManagement = $quoteManagement;
        $this->_quoteRepo = $quoteRepo;
        $this->_logger = $logger;
    }

    /**
     * @see parent
     */
    public function assignCustomer($quoteId, $customerId, $storeId)
    {
        return $this->_quoteManagement->assignCustomer($quoteId, $customerId, $storeId);
    }

    /**
     * @see parent
     */
    public function getCartForCustomer($customerId)
    {
        return $this->_quoteManagement->getCartForCustomer($customerId);
    }

    /**
     * @see \Oracle\M2\Core\Sales\QuoteManagementInterface::getById
     * @param int $cartId
     * @return \Magento\Quote\Api\Data\CartInterface|null
     */
    public function getById($cartId)
    {
        try {
            return $this->_quoteRepo->get($cartId);
        } catch (\Exception $e) {
            $this->_logger->critical($e);
            return null;
        }
    }
}

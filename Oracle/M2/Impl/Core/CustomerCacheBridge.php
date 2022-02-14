<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Impl\Core;

class CustomerCacheBridge implements \Oracle\M2\Core\Customer\CacheInterface
{
    /**
     * @param \Magento\Customer\Model\CustomerRegistry $customerRepo
     */
    public function __construct(
        \Magento\Customer\Model\CustomerRegistry $customerRepo
    ) {
        $this->_customerRepo = $customerRepo;
    }

    /**
     * @see \Oracle\M2\Core\Customer\CacheInterface::getById
     */
    public function getById($customerId)
    {
        try {
            return $this->_customerRepo->retrieve($customerId);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @see parent
     */
    public function getByEmail($email)
    {
        try {
            return $this->_customerRepo->retrieveByEmail($email);
        } catch (\Exception $e) {
            return null;
        }
    }
}

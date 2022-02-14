<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Core\Customer;

interface CacheInterface
{
    /**
     * Gets a customer model from an id
     *
     * @param int $customerId
     * @return \Magento\Customer\Model\Customer
     */
    public function getById($customerId);

    /**
     * Gets a customer model from an email
     *
     * @param string $email
     * @return mixed
     */
    public function getByEmail($email);
}

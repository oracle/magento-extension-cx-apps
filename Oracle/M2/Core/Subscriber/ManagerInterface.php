<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Core\Subscriber;

interface ManagerInterface
{
    const LOCATION_DEFAULT = 'normal';
    const LOCATION_CHECKOUT = 'checkout';
    const LOCATION_ORACLE = 'oracle';

    /**
     * Gets a subscriber by id
     *
     * @param mixed $subscriberId
     * @return mixed
     */
    public function getById($subscriberId);

    /**
     * Gets a subscriber by the email address
     *
     * @param mixed $email
     * @return mixed
     */
    public function getByEmail($email);

    /**
     * Gets a subscriber by the email address, and website id
     *
     * @param mixed $email
     * @return mixed
     */
    public function getBySubscriberEmail($email, $websiteId);

    /**
     * Gets an iterable from the platform for subscribers
     *
     * @return \Magento\Newsletter\Model\ResourceModel\Subscriber\Collection
     */
    public function getCollection();

    /**
     * Unsubscribes locally the email in the platform
     *
     * @param string $email
     * @param boolean $ignoreStatus
     * @param string $location
     * @return boolean
     */
    public function unsubscribe($email, $ignoreStatus = false, $location = 'normal');

    /**
     * Forcefully subscribes a email in the platform
     *
     * @param string $mixed
     * @param boolean $ignoreStatus
     * @param string $location
     * @return boolean
     */
    public function subscribe($email, $ignoreStatus = false, $location = 'normal');
}

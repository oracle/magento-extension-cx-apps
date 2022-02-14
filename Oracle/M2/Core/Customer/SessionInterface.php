<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Core\Customer;

interface SessionInterface
{
    /**
     * Gets a customer object from the session
     *
     * @return mixed
     */
    public function getCustomer();

    /**
     * Log out the customer is session
     *
     * @return void
     */
    public function logout();

    /**
     * Sets the before auth url for post logins
     *
     * @param string $redirectUrl
     * @return void
     */
    public function setBeforeAuthUrl($redirectUrl);
}

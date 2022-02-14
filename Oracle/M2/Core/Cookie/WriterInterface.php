<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Core\Cookie;

use Magento\Framework\Stdlib\Cookie\CookieMetadata;
use Magento\Framework\Stdlib\CookieManagerInterface;

interface WriterInterface
{
    /**
     * Sets a cookie value
     * Note: The following default metadata; Values not mentioned are unset
     *
     * - httpOnly: true
     * - duration: one year
     * - path: /
     *
     * @param string $name
     * @param string $value
     * @param array $metadata
     * @return void
     */
    public function setServerCookie($name, $value, array $metadata = []);

    /**
     * Deletes a cookie
     *
     * @see CookieManagerInterface::deleteCookie
     *
     * @param string $name,
     * @param CookieMetadata $metadata [null]
     * @return void
     */
    public function deleteCookie($name, CookieMetadata $metadata = null);
}

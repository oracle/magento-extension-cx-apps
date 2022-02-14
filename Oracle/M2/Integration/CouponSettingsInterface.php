<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Integration;

interface CouponSettingsInterface
{
    const XML_PATH_COUPON_ENABLED = 'oracle/integration/extensions/coupon_manager/enabled';

    /**
     * Is Coupon module enabled?
     *
     * @param string $scopeType
     * @param mixed $scopeId
     * @return boolean
     */
    public function isCouponEnabled($scopeType = 'default', $scopeId = null);
}

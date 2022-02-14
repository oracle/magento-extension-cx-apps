<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Integration;

class CouponSettings extends \Oracle\M2\Core\Config\ContainerAbstract implements CouponSettingsInterface
{
    /**
     * @see parent
     */
    public function isCouponEnabled($scopeType = 'default', $scopeId = null)
    {
        return $this->_config->isSetFlag(self::XML_PATH_COUPON_ENABLED, $scopeType, $scopeId);
    }
}

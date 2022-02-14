<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Redemption;

class Settings extends \Oracle\M2\Integration\CouponSettings implements \Oracle\M2\Redemption\SettingsInterface
{
    /**
     * @see parent
     */
    public function isToggled($scope = 'default', $scopeId = null)
    {
        return $this->_config->getValue(self::XML_PATH_API_TOGGLE, $scope, $scopeId) == 'api';
    }

    /**
     * @see parent
     */
    public function isEnabled($scope = 'default', $scopeId = null)
    {
        return (
            $this->isToggled($scope, $scopeId) &&
            $this->_config->isSetFlag(self::XML_PATH_COUPON_ENABLED, $scope, $scopeId)
        );
    }

    /**
     * @see parent
     */
    public function isCouponEnabled($scope = 'default', $scopeId = null)
    {
        return (
            !$this->isToggled($scope, $scopeId) &&
            parent::isCouponEnabled($scope, $scopeId)
        );
    }
}

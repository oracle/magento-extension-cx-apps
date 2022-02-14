<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Redemption;

interface SettingsInterface extends \Oracle\M2\Connector\Event\HelperInterface
{
    const XML_PATH_API_TOGGLE = 'oracle/integration/extensions/coupon_manager/toggle_api';

    /**
     * Determines if the user has turned on REST API intergration
     *
     * @param string $scope
     * @param mixed $scopeId
     * @return boolean
     */
    public function isToggled($scope = 'default', $scopeId = null);
}

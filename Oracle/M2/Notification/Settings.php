<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Notification;

class Settings extends \Oracle\M2\Core\Config\ContainerAbstract implements SettingsInterface
{
    /**
     * @see parent
     */
    public function isEnabled($scope = 'default', $scopeId = null)
    {
        return $this->_config->isSetFlag(self::XML_PATH_ENABLED, $scope, $scopeId);
    }

    /**
     * @see parent
     */
    public function getNotificationEmail($scope = 'default', $scopeId = null)
    {
        return $this->_config->getValue(self::XML_PATH_EMAIL, $scope, $scopeId);
    }
}

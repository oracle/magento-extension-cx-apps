<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Connector;

class Settings extends \Oracle\M2\Core\Config\ContainerAbstract implements \Oracle\M2\Connector\SettingsInterface
{
    /**
     * @see parent
     */
    public function getSiteId($scope = 'default', $scopeId = null)
    {
        return $this->_config->getValue(self::XML_PATH_SITEID, $scope, $scopeId);
    }

    /**
     * @see parent
     */
    public function getMaskId($scope = 'default', $scopeId = null)
    {
        return $this->_config->getValue(self::XML_PATH_MASKID, $scope, $scopeId);
    }

    /**
     *
     * @see \Oracle\M2\Connector\SettingsInterface::getEik
     * @param string $scope
     * @param null $scopeId
     * @return string
     */
    public function getEik($scope = 'default', $scopeId = null)
    {
        return $this->_config->getValue(self::XML_PATH_EIK, $scope, $scopeId);
    }

    /**
     * @see \Oracle\M2\Connector\SettingsInterface::getCustomUrl
     * @param string $scope ['default']
     * @param int $scopeId [null]
     * @return string
     */
    public function getCustomUrl($scope = 'default', $scopeId = null)
    {
        return $this->_config->getValue(self::XML_PATH_CUSTOM_URL, $scope, $scopeId);
    }

    /**
     *
     * @see \Oracle\M2\Connector\SettingsInterface::getServiceUrl
     * @param string $scope
     * @param null $scopeId
     * @return string
     */
    public function getServiceUrl($scope = 'default', $scopeId = null)
    {
        $serviceUrl = getenv(self::SERVICE_URL_OVERRIDE_KEY);

        return $serviceUrl ?: self::SERVICE_URL;
    }

    /**
     * @see parent
     */
    public function isOrderService($scope = 'default', $scopeId = null)
    {
        return $this->_config->isSetFlag(self::XML_PATH_ORDER_SERVICE, $scope, $scopeId);
    }

    /**
     * @see parent
     */
    public function isToggled($endpointId, $scope = 'default', $scopeId = null)
    {
        $path = sprintf(self::XML_PATH_TOGGLE_PREFIX, $endpointId);
        return $this->_config->isSetFlag($path, $scope, $scopeId);
    }

    /**
     * @see parent
     */
    public function isTestMode($scope = 'default', $scopeId = null)
    {
        return $this->_config->isSetFlag(self::XML_PATH_TEST_MODE, $scope, $scopeId);
    }

    /**
     * @see parent
     */
    public function isFlushDisabled($scope = 'default', $scopeId = null)
    {
        return $this->_config->isSetFlag(self::XML_PATH_DISABLE_FLUSH, $scope, $scopeId);
    }

    /**
     * @see parent
     */
    public function isEventQueued($scope = 'default', $scopeId = null)
    {
        return true;
    }

    /**
     * @see parent
     */
    public function isBrowseRecovery($scope = 'default', $scopeId = null)
    {
        return (bool) $this->_config->isSetFlag(self::XML_PATH_BROWSE_RECOVERY, $scope, $scopeId);
    }

    /**
     * @see parent
     */
    public function isCartRecovery($scope = 'default', $scopeId = null)
    {
        return (bool) $this->_config->isSetFlag(self::XML_PATH_CART_RECOVERY, $scope, $scopeId);
    }

    /**
     * @see parent
     */
    public function cartRecoveryApiToggle($scope = 'default', $scopeId = null)
    {
        return $this->_config->getValue(self::XML_PATH_CART_RECOVERY_API_TOGGLE, $scope, $scopeId);
    }

    /**
     * {@inheritDoc}
     * @see \Oracle\M2\Connector\SettingsInterface::getBatchSize()
     */
    public function getBatchSize($scope = 'default', $scopeId = null)
    {
        return $this->_config->getValue(self::XML_PATH_BATCH_SIZE, $scope, $scopeId);
    }

    /**
     * {@inheritdoc}
     */
    public function getTidHash($scopeType = 'default', $scopeCode = null)
    {
        return preg_replace('/[\-;,=\s]/', '', base64_encode($this->getSiteId($scopeType, $scopeCode)));
    }

    /**
     * {@inheritdoc}
     */
    public function getTidKey($scopeType = 'default', $scopeId = null)
    {
        return self::COOKIE_TID_PREFIX . $this->getTidHash($scopeType, $scopeId);
    }
}

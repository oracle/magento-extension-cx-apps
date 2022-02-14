<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Connector;

interface SettingsInterface
{
    const XML_PATH_SITEID = 'oracle/general/settings/siteId';
    const XML_PATH_MASKID = 'oracle/general/settings/maskId';
    const XML_PATH_EIK = 'oracle/general/settings/externalInstallKey';
    const XML_PATH_CUSTOM_URL = 'oracle_local/general/custom_url';
    const XML_PATH_ORDER_SERVICE = 'oracle/general/features/enableOrderService';
    const XML_PATH_TEST_MODE = 'oracle/advanced/extensions/testImport/enabled';
    const XML_PATH_DISABLE_FLUSH = 'oracle/advanced/extensions/testImport/disableFlush';
    const XML_PATH_BATCH_SIZE = 'oracle/advanced/extensions/performance/batchSize';
    const XML_PATH_BROWSE_RECOVERY = 'oraclesoftware/integration/extensions/browse_recovery/enabled';
    const XML_PATH_CART_RECOVERY = 'oraclesoftware/integration/extensions/cart_recovery/enabled';
    const XML_PATH_CART_RECOVERY_API_TOGGLE = 'oraclesoftware/integration/extensions/cart_recovery/toggle_api';

    const XML_PATH_TOGGLE_PREFIX = 'oracle/toggle/%s';

    const SERVICE_URL_OVERRIDE_KEY = 'ORACLE_SERVICE_URL';
    const SERVICE_URL = 'https://fiddler.oracleps.com';

    const COOKIE_TID_PREFIX = 'tid_';

    /**
     * Gets the Oracle site hash for the registered scope
     *
     * @param string $scope
     * @param int $scopeId
     * @return string
     */
    public function getSiteId($scope = 'default', $scopeId = null);

    /**
     * Gets the Oracle maskId for authenticated imports
     *
     * @param string $scope
     * @param int $scopeId
     * @return string
     */
    public function getMaskId($scope = 'default', $scopeId = null);

    /**
     * Gets the EIK

     * @param string $scope
     * @param int $scopeId
     * @return string
     */
    public function getEik($scope = 'default', $scopeId = null);

    /**
     * Gets the custom pingback URL for Middleware use
     * 
     * @param string $scope ['default]
     * @param null $scopeId [null]
     * @return string
     */
    public function getCustomUrl($scope = 'default', $scopeId = null);

    /**
     * Gets the Connector service URL

     * @param string $scope
     * @param int $scopeId
     * @return string
     */
    public function getServiceUrl($scope = 'default', $scopeId = null);

    /**
     * Determines if the order service is enabled for the account
     *
     * @param string $scope
     * @param int $scopeId
     * @return bool
     */
    public function isOrderService($scope = 'default', $scopeId = null);

    /**
     * Determines if the extension has been toggle on
     *
     * @param string endpointId
     * @param string $scope
     * @param mixed $scopeId
     * @return boolean
     */
    public function isToggled($endpointId, $scope = 'default', $scopeId = null);

    /**
     * Determines if this extension is in test mode
     *
     * @param string $scope
     * @param mixed $scopeId
     * @return boolean
     */
    public function isTestMode($scope = 'default', $scopeId = null);

    /**
     * Determines if flush jobs should be skipped
     *
     * @param string $scope
     * @param mixed $scopeId
     * @return boolean
     */
    public function isFlushDisabled($scope = 'default', $scopeId = null);

    /**
     * Determines if this event is queueable
     *
     * @param mixed $scope
     * @param mixed $scopeId
     * @return boolean
     */
    public function isEventQueued($scope = 'default', $scopeId = null);

    /**
     * Determines if Browse Recovery Integration is enabled
     *
     * @param string $scope
     * @param int [null] $scopeId
     * @return bool
     */
    public function isBrowseRecovery($scope = 'default', $scopeId = null);

    /**
     * Determines if Cart Recovery Integration is enabled
     *
     * @param string $scope
     * @param int [null] $scopeId
     * @return bool
     */
    public function isCartRecovery($scope = 'default', $scopeId = null);

    /**
     * Gets the current Cart Recovery Integration method
     *
     * @param string $scope
     * @param int [null] $scopeId
     * @return string
     */
    public function cartRecoveryApiToggle($scope = 'default', $scopeId = null);
    
    /**
     * Returns the batch-size to be used when sending data to Sarlacc in response
     * to a request from the Middleware application.
     *
     * @param string ['default'] $scope
     * @param int [null] $scopeId
     * @return int
     */
    public function getBatchSize($scope = 'default', $scopeId = null);

    /**
     * Gets a tid hash used in the cookie suffix
     *
     * @param string $scope
     * @param int $scopeId
     * @return string
     */
    public function getTidHash($scope = 'default', $scopeId = null);

    /**
     * Gets the tid cookie key
     * 
     * @param string $scope
     * @param int $scopeId
     * @return string
     */
    public function getTidKey($scope = 'default', $scopeId = null);
}

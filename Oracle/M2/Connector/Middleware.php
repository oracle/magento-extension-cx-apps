<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Connector;

class Middleware implements MiddlewareInterface, ConnectorInterface
{
    const BASE_URL_OVERRIDE_KEY = 'ORACLE_MIDDLEWARE_BASEURL';
    const BASE_URL = 'https://apps.p02.eloqua.com';
    const MDLWE_REGISTER = "/ecom/ams/lifecycle/application/request/magento/{siteId}";
    const MDLWE_DEREGISTER = "/ecom/ams/lifecycle/application/request/magento/unregister/{siteId}";
    const MDLWE_SETTINGS = "/ecom/ams/lifecycle/application/request/magento/settings/{siteId}";

    const MDLWE_SCRIPT = "/connector/scripts";
    const MDLWE_EIK = "/connector/eiklookup";
    const MIDWE_VERSION_CHECK = '/m2/versioncheck';
    const TZ_CONFIG = 'general/locale/timezone';
    const XAUTHORIZATION = "X-Authorization";
    const USE_CUSTOM_ADMIN = "admin/url/use_custom";
    const CUSTOM_ADMIN_URL = "admin/url/custom";
    const USE_CUSTOM_PATH = "admin/url/use_custom_path";
    const CUSTOM_ADMIN_PATH = "admin/url/custom_path";

    const EIK_KEY = 'externalInstallKey';
    const IK_KEY = 'installKey';

    /** @var \Oracle\M2\Common\Transfer\Adapter  */
    protected $_client;

    /** @var \Oracle\M2\Common\Serialize\BiDirectional  */
    protected $_encoder;

    /** @var \Oracle\M2\Core\Log\LoggerInterface  */
    protected $_logger;

    /** @var \Oracle\M2\Core\MetaInterface  */
    protected $_meta;

    /** @var \Oracle\M2\Core\EncryptorInterface  */
    protected $_encrypt;

    /** @var \Oracle\M2\Core\Store\ManagerInterface  */
    protected $_storeManager;

    /** @var \Oracle\M2\Core\Event\ManagerInterface  */
    protected $_eventManager;

    /** @var \Oracle\M2\Core\Config\ManagerInterface  */
    protected $_config;

    /** @var SettingsInterface  */
    protected $_settings;

    /** @var string  */
    protected $_baseUrl;

    /**
     * @param \Oracle\M2\Common\Transfer\Adapter $client
     * @param \Oracle\M2\Common\Serialize\BiDirectional $encoder
     * @param \Oracle\M2\Core\Log\LoggerInterface $logger
     * @param \Oracle\M2\Core\MetaInterface $meta
     * @param \Oracle\M2\Core\EncryptorInterface $encrypt
     * @param \Oracle\M2\Core\Event\ManagerInterface $eventManager
     * @param \Oracle\M2\Core\Store\ManagerInterface $storeManager
     * @param \Oracle\M2\Core\Config\ManagerInterface $config
     * @param SettingsInterface $settings
     */
    public function __construct(
        \Oracle\M2\Common\Transfer\Adapter $client,
        \Oracle\M2\Common\Serialize\BiDirectional $encoder,
        \Oracle\M2\Core\Log\LoggerInterface $logger,
        \Oracle\M2\Core\MetaInterface $meta,
        \Oracle\M2\Core\EncryptorInterface $encrypt,
        \Oracle\M2\Core\Event\ManagerInterface $eventManager,
        \Oracle\M2\Core\Store\ManagerInterface $storeManager,
        \Oracle\M2\Core\Config\ManagerInterface $config,
        SettingsInterface $settings
    ) {
        $this->_client = $client;
        $this->_encoder = $encoder;
        $this->_logger = $logger;
        $this->_meta = $meta;
        $this->_encrypt = $encrypt;
        $this->_eventManager = $eventManager;
        $this->_storeManager = $storeManager;
        $this->_config = $config;
        $this->_settings = $settings;
        $this->_baseUrl = $this->getServiceBaseUrl();
    }

    /**
     * @param RegistrationInterface $registration
     * @return string
     */
    private function generateInstallKey(RegistrationInterface $registration)
    {
        return "{$registration->getConnectorKey()}-magento-{$registration->getScopeHash()}-{$registration->getEnvironment()}";
    }

    /////////////////////////
    // MiddlewareInterface //
    /////////////////////////
    /**
     * @see parent
     */
    public function register(RegistrationInterface $model)
    {
        return $this->_postRegistration($model, $this->_baseUrl . self::MDLWE_REGISTER);
    }

    /**
     * @see parent
     */
    public function deregister(RegistrationInterface $registration)
    {

        $success = $this->_postRegistrationDeregister($registration, $this->_baseUrl . self::MDLWE_DEREGISTER);
        if ( $success==1 && $registration->getIsActive()) {
            try {
                $this->_recursiveDelete($this->scopeTree($registration));
            } catch (\Exception $e) {
                $this->_logger->critical($e);
                $success = 0;
            }
        }
        return $success;
    }

    /**
     * @see parent
     */
    public function sync(RegistrationInterface $model)
    {
        try {
            $settings = $this->settings($model);
            // $eik = $this->getEik($model);
            //if ($eik) {
            //   $settings[self::EIK_KEY] = $eik;
            // }
            $this->_walkSettings($model, $settings, [$this, '_saveSetting']);
            return true;
        } catch (\Exception $e) {
            $this->_logger->critical($e);
            return false;
        }
    }

    /**
     * @see parent
     */
    public function triggerFlush(RegistrationInterface $model)
    {
        try {
            $canFlush = !$this->_settings->isFlushDisabled($model->getScope(), $model->getScopeId());
            if ($canFlush && $model->getIsActive()) {
                $scriptUrl = $this->_baseUrl . self::MDLWE_SCRIPT;
                $installKey = $this->generateInstallKey($model);
                $script = new \Oracle\M2\Connector\Discovery\Script($model);
                $this->_eventManager->dispatch('oracle_connector_trigger_flush', [
                    'script' => $script
                ]);
                foreach ($script->getJobs() as $jobInfo) {
                    $request = $this->_client->createRequest('POST', $scriptUrl)
                        ->header(self::XAUTHORIZATION, "Bearer {$installKey}")
                        ->header("Content-Type", $this->_encoder->getMimeType())
                        ->body($this->_encoder->encode($jobInfo));
                    $response = $request->respond();
                    if ($response->code() >= 300) {
                        $this->_logger->critical("Failed to send {$jobInfo['id']}");
                    }
                }
            }
            return true;
        } catch (\Exception $e) {
            $this->_logger->critical($e);
            return false;
        }
    }

    /**
     * @see parent
     */
    public function installKey(RegistrationInterface $model)
    {
        return rawurlencode($this->_encrypt->encrypt($model->getScopeHash()));
    }

    /**
     * @see parent
     */
    public function defaultStoreId($scopeType = 'default', $scopeId = '0')
    {
        switch ($scopeType) {
            case 'default':
                // `true` here gets the system default website
                return $this->_storeManager
                    ->getWebsite(true)
                    ->getDefaultGroup()
                    ->getDefaultStoreId();
            case 'websites':
            case 'website':
                return $this->_storeManager
                    ->getWebsite($scopeId)
                    ->getDefaultGroup()
                    ->getDefaultStoreId();
            default:
                return $scopeId;
        }
    }

    /**
     * @see parent
     */
    public function storeScopes(RegistrationInterface $model, $includeSelf = false)
    {
        return $this->_storeScopes($model->getScope(), $model->getScopeId(), $includeSelf);
    }

    /////////////////////////
    //  ConnectorInterface //
    /////////////////////////
    /**
     * @see parent
     */
    public function scopeTree(RegistrationInterface $model)
    {
        switch ($model->getScope()) {
            case 'default':
                return $this->_convertInstall();
            case 'website':
                $website = $this->_storeManager->getWebsite($model->getScopeId());
                return $this->_convertWebsite($website);
            default:
                $store = $this->_storeManager->getStore($model->getScopeId());
                return $this->_convert('store', $store);
        }
    }

    /**
     * @see parent
     */
    public function discovery(RegistrationInterface $model)
    {
        $defaultStore = $this->_defaultStore($model);
        $urlParts = parse_url($this->_baseUrl($model));
        return [
            'extensionGroups' => $this->_gatherEndpoints($model),
            'instanceInfo' => [
                'id' => $model->getScopeHash(),
                'name' => $model->getName(),
                'type' => $this->_meta->getName(),
                'extensionVersion' => $this->_meta->getExtensionVersion(),
                'platformVersion' => $this->_meta->getVersion() . ' (' . $this->_meta->getEdition() . ')',
                'features' => [ 'promotion' => true ],
                'properties' => [
                    'type' => ucfirst(strtolower($model->getEnvironment())),
                    'hostname' => $urlParts['scheme'] . '://' .  $urlParts['host'],
                    'timezone' => $defaultStore->getConfig(self::TZ_CONFIG)
                ]
            ]
        ];
    }

    /**
     * @see parent
     */
    public function endpoint(RegistrationInterface $model, $serviceName)
    {
        $endpoint = new \Oracle\M2\Connector\Discovery\Endpoint();
        $eventAreaName = "oracle_connector_{$serviceName}_endpoint";
        $this->_eventManager->dispatch($eventAreaName, [
            'endpoint' => $endpoint,
            'registration' => $model
        ]);
        $this->_eventManager->dispatch("{$eventAreaName}_additional", [
            'endpoint' => $endpoint,
            'registration' => $model
        ]);
        $endpointData = $endpoint->getInformation();
        foreach ($endpointData as $key => $settings) {
            $value = $settings;
            if (!preg_match('/^(type|autoConfig)/', $key)) {
                $value = $this->sortAndSet($settings);
            }
            $endpointData[$key] = $value;
        }
        return $endpointData;
    }

    /**
     * @see parent
     */
    public function executeScript(RegistrationInterface $model, $script)
    {
        return $this->_executeInfo($model, 'script', $script);
    }

    /**
     * @see parent
     */
    public function source(RegistrationInterface $model, $sourceId, $params = [])
    {
        $source = new \Oracle\M2\Connector\Discovery\Source($model);
        // $sourceId can be like coupon_code
        $eventAreaName = "oracle_connector_source_$sourceId";
        // Hand off implementation to extension due to source variety
        $this->_eventManager->dispatch($eventAreaName, [
            'source' => $source->setParams($params),
        ]);
        return $source->getResults();
    }

    /**
     * @see parent
     */
    public function settings(RegistrationInterface $model)
    {
        $request = $this->_postAuthForm($model, $this->_baseUrl . self::MDLWE_SETTINGS);
        $response = $request->respond();
        if ($response->code() >= 300) {
            throw new \Oracle\M2\Common\Transfer\Exception("Failed to retrieve settings.", $response->code(), $request);
        }
        return $this->_encoder->decode($response->body());
    }

    /**
     * @see ConnectorInterface::getEik
     */
    public function getEik(RegistrationInterface $model)
    {
        $uri = $this->_baseUrl . self::MDLWE_EIK;
        $installKey = $this->generateInstallKey($model);
        $request = $this->_client->createRequest('GET', $uri)
            ->header(self::XAUTHORIZATION, "Bearer {$installKey}")
            ->header("Content-Type", $this->_encoder->getMimeType());
        $response = $request->respond();
        if ($response->code() >= 300) {
            throw new \Oracle\M2\Common\Transfer\Exception("Failed to retrieve EIK.", $response->code(), $request);
        }

        $body = $this->_encoder->decode($response->body());
        return isset($body[self::EIK_KEY]) ? $body[self::EIK_KEY] : '';
    }

    /**
     * @see parent
     */
    public function sortAndSet(array $settings)
    {
        usort($settings, [$this, '_applySortOrder']);
        return array_map([$this, '_flattenSetting'], $settings);
    }

    /**
     * Gets the Middleware service base URL
     * Note that this value can be overridden by an environment variable
     *
     * @return string
     */
    public static function getServiceBaseUrl()
    {
        $overrideBaseUrl = trim(getenv(self::BASE_URL_OVERRIDE_KEY));
        return $overrideBaseUrl ?: self::BASE_URL;
    }

    /**
     * @return string
     */
    public static function getVersionCheckUrl()
    {
        return self::getServiceBaseUrl() . self::MIDWE_VERSION_CHECK;
    }

    ////////////////////////////
    // protected Helper Methods //
    ////////////////////////////
    /**
     * Executes either a Middleware script or job execution
     *
     * @param \Oracle\M2\Connector\RegistrationInterface $model
     * @param string $name
     * @param array $object
     * @return array
     */
    protected function _executeInfo(RegistrationInterface $model, $name, $object)
    {
        $execution = new \Oracle\M2\Connector\Discovery\Execution($model);
        $objectId = "{$object['extensionId']}_{$object["id"]}";
        $this->_logger->debug($objectId);
        $this->_eventManager->dispatch("oracle_connector_{$name}_{$objectId}", [
            $name => $execution->setObject($object)
        ]);
        return $execution->getResults();
    }

    /**
     * Gathers all of the available extension groups available on the server
     *
     * @param \Oracle\M2\Connector\RegistrationInterface $model
     * @return array
     */
    protected function _gatherEndpoints(RegistrationInterface $model)
    {
        $discovery = new \Oracle\M2\Connector\Discovery();
        $this->_eventManager->dispatch("oracle_connector_gather_endpoints", [
            'discovery' => $discovery,
            'registration' => $model
        ]);
        return $this->sortAndSet($discovery->getGroups());
    }

    /**
     * Applies the sort_order field on the various documents
     *
     * @param array $groupA
     * @param array $groupB
     * @return int
     */
    protected function _applySortOrder($groupA, $groupB)
    {
        if ($groupA['sort_order'] == $groupB['sort_order']) {
            return 0;
        }
        return ($groupA['sort_order'] < $groupB['sort_order']) ? -1 : 1;
    }

    /**
     * Performs the client transfer of registration material
     *
     * @param Oracle\M2\Connector\RegistrationInterface $model
     * @param string $baseUrl
     * @return boolean
     */
    protected function _postRegistration(RegistrationInterface $model, $baseUrl)
    {
        try {

            return $this->_postAuthForm($model, $baseUrl)->respond()->code() < 300;
        } catch (\Exception $e) {
            $this->_logger->critical($e);
            return false;
        }
    }

    /**
     * Performs the client transfer of registration material
     *
     * @param Oracle\M2\Connector\RegistrationInterface $model
     * @param string $baseUrl
     * @return integer 1 on success -1 if dependency found 0 for other failures
     */
    protected function _postRegistrationDeregister(RegistrationInterface $model, $baseUrl)
    {
        try {
            $responseCode = $this->_postAuthForm($model, $baseUrl)->respond()->code();
            if($responseCode < 300){
                return 1;
            }
            elseif ($responseCode == 409)
            {
                return -1;
            }
            else return 0;
        } catch (\Exception $e) {
            $this->_logger->critical($e);
            return 0;
        }
    }

    /**
     * Performs the client transfer of registration material
     *
     * @param Oracle\M2\Connector\RegistrationInterface $model
     * @param string $baseUrl
     * @return mixed
     */
    protected function _deleteRegistration(RegistrationInterface $model, $baseUrl)
    {
        try {
            $responseCode =  $this->_postAuthForm($model, $baseUrl)->respond()->code();
            return $responseCode;

        } catch (\Exception $e) {
            $this->_logger->critical($e);
            return false;
        }
    }


    /**
     * Performs the client transfer of registration auth material
     *
     * @param \Oracle\M2\Connector\RegistrationInterface $model
     * @param string $baseUrl
     * @return \Oracle\M2\Common\Transfer\Request
     */
    protected function _postAuthForm(RegistrationInterface $model, $baseUrl)
    {
        $baseUrl = str_replace('{siteId}', $model->getConnectorKey(), $baseUrl);
        $contentTypeHeader = $this->_encoder->getMimeType();
        $body = $this->_authForm($model);
        $encodedBody = $this->_encoder->encode($body);

        $debugMsg = "AUTH FORM POST REQUEST CONTENT: \n"
            . "URI: {$baseUrl}\n"
            . "Header: Content-Type={$contentTypeHeader}\n"
            . "Body: " . var_export($body, true) . "\n"
            . "Encoded body: {$encodedBody}\n";
        $this->_logger->debug($debugMsg);

        return $this->_client->createRequest('POST', $baseUrl)
            ->header('Content-Type', $contentTypeHeader)
            ->body($encodedBody);
    }

    /**
     * Gets the authForm for middleware management
     *
     * @param \Oracle\M2\Connector\RegistrationInterface $model
     * @return array
     */
    protected function _authForm(RegistrationInterface $model)
    {
        $credentials = [$this->installKey($model)];
        if ($model->getIsProtected()) {
            $credentials[] = $model->getUsername();
            $credentials[] = $model->getPassword();
        }
        return [
            'authType' => 'BASIC',
            'instanceType' => $model->getEnvironment(),
            'accountId' => $model->getScopeHash(),
            'username' => $this->_baseUrl($model),
            'password' => implode('::', $credentials)
        ];
    }

    /**
     * Gets the full pingback URL for discovery
     *
     * @param \Oracle\M2\Connector\RegistrationInterface $model
     * @return string
     */
    protected function _baseUrl(RegistrationInterface $model)
    {
        $defaultStore = $this->_defaultStore($model);
        $oracleCustomUrl = $this->_settings->getCustomUrl();
        if ($oracleCustomUrl) {
            $baseUrl = trim($oracleCustomUrl, '/') . '/';
        } else {
            $customUrl = (bool)$defaultStore->getConfig(self::USE_CUSTOM_ADMIN);
            if ($customUrl) {
                $baseUrl = $defaultStore->getConfig(self::CUSTOM_ADMIN_URL);
            } else {
                $isSecure = method_exists($defaultStore, 'isAdminUrlSecure')
                    ? $defaultStore->isAdminUrlSecure()
                    : $defaultStore->getConfig('web/secure/use_in_adminhtml');
                $baseUrl = $defaultStore->getBaseUrl('link', $isSecure);
            }
        }
        $customPath = (bool)$defaultStore->getConfig(self::USE_CUSTOM_PATH);
        if ($customPath) {
            $area = $defaultStore->getConfig(self::CUSTOM_ADMIN_PATH);
        } else {
            $area = $this->_meta->getAdminFrontName();
        }
        $suffix = $model->getPlatformSuffix();
        if (!empty($suffix)) {
            $suffix = trim($suffix, '/') . '/';
        }
        return $baseUrl . $area . '/' . $suffix;
    }

    /**
     * Gets the default store to handle frontend interaction
     *
     * @param \Oracle\Connector\Model\RegistrationInterface $model
     * @return mixed
     */
    protected function _defaultStore(RegistrationInterface $model)
    {
        $storeId = $this->defaultStoreId($model->getScope(), $model->getScopeId());
        return is_null($storeId) ? null : $this->_storeManager->getStore($storeId);
    }

    /**
     * Flattens the document setting
     *
     * @param array $setting
     * @return array
     */
    protected function _flattenSetting($setting)
    {
        return $setting['definition'];
    }

    /**
     * Converts the system install into a scope object
     *
     * @return array
     */
    protected function _convertInstall()
    {
        $tree = [
            'id' => 'default.0',
            'name' => 'Default',
            'children' => []
        ];
        foreach ($this->_storeManager->getWebsites() as $website) {
            $tree['children'][] = $this->_convertWebsite($website);
        }
        return $tree;
    }

    /**
     * Converts a website into a scope object
     *
     * @param mixed $website
     * @return array
     */
    protected function _convertWebsite($website)
    {
        $tree = $this->_convert('website', $website);
        foreach ($website->getStores() as $store) {
            $tree['children'][] = $this->_convert('store', $store);
        }
        return $tree;
    }

    /**
     * Converts a store or website model into a scope object
     *
     * @param string $type
     * @param mixed $model
     * @return array
     */
    protected function _convert($type, $model)
    {
        return [
            'id' => "{$type}.{$model->getId()}",
            'name' => $model->getName(),
            'children' => []
        ];
    }

    /**
     * Creates a list of store scopes from the root scope
     *
     * @param string $scopeName
     * @param mixed $scopeId
     * @param boolean $includeSelf
     * @return array
     */
    protected function _storeScopes($scopeName, $scopeId, $includeSelf = false)
    {
        $scopes = [];
        $stores = [];
        $defaultStoreId = $this->defaultStoreId($scopeName, $scopeId);
        switch ($scopeName) {
            case 'stores':
            case 'store':
                $stores[] = $this->_storeManager->getStore($scopeId);
                break;
            case 'website':
            case 'websites':
                $website = $this->_storeManager->getWebsite($scopeId);
                $stores = $website->getStores();
                break;
            default:
                $stores = $this->_storeManager->getStores();
        }
        foreach ($stores as $store) {
            if (!$includeSelf && $defaultStoreId == $store->getId()) {
                continue;
            }
            $scopes[$store->getCode()] = $store->getId();
        }
        return $scopes;
    }

    /**
     * Walk the connector settings and clear out all of the fields
     *
     * @param \Oracle\Connector\Model\Registration $model
     * @param array $settings
     * @param callable $callback
     */
    protected function _walkSettings($model, $settings, $callback)
    {
        // it add to core config data table
        // its json
        $scopeName = $model->getScope();
        if ($scopeName != 'default') {
            $scopeName .= 's';
        }
        $this->_recursiveDelete($this->scopeTree($model));
        foreach ($settings as $general => $value) {
            $path = "oracle/general/settings/{$general}";
            if ($general == 'featureMap') {
                foreach ($value as $feature => $flag) {
                    if (preg_match('/^magento_(.+)/', $feature, $matches)) {
                        $path = "oracle/toggle/{$matches[1]}";
                    } else {
                        $path = "oracle/general/features/{$feature}";
                    }
                    call_user_func($callback, $path, (int) $flag, $scopeName, $model->getScopeId());
                }
            } elseif ($general == 'siteId' || $general == 'maskId' || $general == self::EIK_KEY) {
                call_user_func($callback, $path, $value, $scopeName, $model->getScopeId());
            }
        }

        $sections = ['extensions', 'objects'];
        $scopedSettings = $settings['settings'];
        usort($scopedSettings, [$this, '_sortScopedSettings']);
        foreach ($scopedSettings as $scope) {
            list($sName, $sId) = explode('.', $scope['scope']);
            $stores = [];
            switch ($sName) {
                case 'default':
                    break;
                case 'website':
                    $stores = $this->_storeScopes($sName, $sId, true);
                default:
                    $sName .= 's';
            }
            foreach ($scope['groups'] as $endpoint) {
                foreach ($sections as $section) {
                    $this->_sectionWalk($section, $sName, $sId, $endpoint, $callback);
                    foreach ($stores as $storeId) {
                        $this->_sectionWalk($section, 'stores', $storeId, $endpoint, $callback);
                    }
                }
                $eventAfter = "oracle_{$endpoint['id']}_setting_sync_after";
                $this->_eventManager->dispatch($eventAfter, [
                    'settings' => $endpoint,
                    'scope_name' => $sName,
                    'scope_id' => $sId,
                    'children' => $stores
                ]);
            }
        }
        $this->_config->reinit();
        $this->_storeManager->reinitStores();
    }

    /**
     * Saves the scoped sections for the endpoint
     *
     * @param string $section
     * @param string $scopeName
     * @param int $scopeId
     * @param array $endpoint
     * @param callable $callback
     * @return void
     */
    protected function _sectionWalk($section, $scopeName, $scopeId, $endpoint, $callback)
    {
        foreach ($endpoint[$section] as $object) {
            foreach ($object['fields'] as $field) {
                // have to change this
                // $field['value'] = ($field['value'] === 'true');
                if($field['value'] === "true") {
                    $field['value'] = true;
                }
                if($field['value'] === "false") {
                    $field['value'] = false;
                }
                if(is_numeric($field['value'])) {
                    $field['value'] = intval($field['value']);
                }
                // is_numeric($field['value']) ; cast it to int
                // $num = intval("10");
                if ($section == 'objects') {
                    $field['value']['id'] = $field['id'];
                    $field['value'] = serialize($field['value']);
                    $field['id'] = 'object_' . preg_replace('|[\-\s]|', '', $field['id']);
                } elseif (is_bool($field['value'])) {
                    $field['value'] = (int)($field['value']);
                } elseif (is_array($field['value'])) {
                    $field['value'] = implode(',', $field['value']);
                }
                $path = "oracle/{$endpoint['id']}/{$section}/{$object['id']}/{$field['id']}";
                call_user_func($callback, $path, $field['value'], $scopeName, $scopeId);
            }
        }
    }

    /**
     * Saves the setting directly into the DB
     *
     * @param string $path
     * @param mixed $value
     * @param string $scopeName
     * @param mixed $scopeId
     * @return void
     */
    protected function _saveSetting($path, $value, $scopeName, $scopeId)
    {
        $this->_config->save($path, $value, $scopeName, $scopeId);
    }

    /**
     * @param array $tree
     * @param string $suffix
     * @return void
     */
    protected function _recursiveDelete($tree, $suffix = '')
    {
        list($scopeName, $scopeId) = explode('.', $tree['id']);
        // this will delete from the config table.
        $this->_config->deleteAll('oracle/' . $suffix, $scopeName, $scopeId);
        foreach ($tree['children'] as $child) {
            $this->_recursiveDelete($child, $suffix);
        }
    }

    /**
     * User defined sort on settings to guarantee the scope order
     *
     * @param array $scopeA
     * @param array $scopeB
     * @return int
     */
    protected function _sortScopedSettings($scopeA, $scopeB)
    {
        preg_match('/([^\.])+/', $scopeA['scope'], $matchA);
        preg_match('/([^\.])+/', $scopeB['scope'], $matchB);
        if ($matchA[1] == $matchB[1]) {
            return 0;
        } elseif ($matchA[1] == 'website') {
            return $matchB[1] == 'default' ? 1 : -1;
        } else {
            return 1;
        }
    }
}

<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Connector\Event;

use Oracle\Connector\Model\Registration;
use Magento\Store\Model\Store;

class Platform implements PlatformInterface
{
    const SARLACC_URL_OVERRIDE_KEY = 'ORACLE_SARLACC_URL';
    // const SARLACC = 'https://apps.p02.eloqua.com/ecom/ams/app/magento/application/request/object/ingest';
    const SARLACC = 'https://apps.p02.eloqua.com/ecom/ams/app/magento/application/request/object/ingest';

    const SARLACC_TYPE = 'OracleRecord';
    const SARLACC_VERSION = '1.0.0';
    // Note: We are exposing a new interface for the ingest request.
    const RESPONSYS_EVENT_URL_OVERRIDE_KEY = 'RESPONSYS_EVENT_URL';
    // This is a placeholder url, and it will change based on the environment where we deploy the app.
    // replace APP_HOST with the actual url.
    const RESPONSYS_EVENT_URL = 'https://apps.p02.eloqua.com/ecom/ams/app/magento/application/request/message/ingest';

    /** @var \Oracle\M2\Common\Transfer\Adapter */
    protected $_client;

    /** @var \Oracle\M2\Common\Serialize\BiDirectional */
    protected $_encoder;

    /** @var \Oracle\M2\Connector\SettingsInterface */
    protected $_settings;

    /** @var \Oracle\M2\Core\Log\LoggerInterface */
    protected $_logger;

    /** @var \Oracle\M2\Core\MetaInterface */
    protected $_meta;

    /** @var string */
    protected $_baseUrl;

    /** @var string */
    protected $_eventBaseUrl;
    /**
     * @param \Oracle\M2\Common\Transfer\Adapter $client
     * @param \Oracle\M2\Common\Serialize\BiDirectional $encoder
     * @param \Oracle\M2\Connector\SettingsInterface $settings
     * @param \Oracle\M2\Core\Log\LoggerInterface $logger
     * @param \Oracle\M2\Core\MetaInterface $meta
     */
    public function __construct(
        \Oracle\M2\Common\Transfer\Adapter $client,
        \Oracle\M2\Common\Serialize\BiDirectional $encoder,
        \Oracle\M2\Connector\SettingsInterface $settings,
        \Oracle\M2\Core\Log\LoggerInterface $logger,
        \Oracle\M2\Core\MetaInterface $meta
    ) {
        $this->_client = $client;
        $this->_encoder = $encoder;
        $this->_settings = $settings;
        $this->_logger = $logger;
        $this->_meta = $meta;

        $this->_baseUrl = $this->getBaseUrl();
        $this->_eventBaseUrl = $this->getEventBaseUrl();
    }

    /**
     * @return string
     */
    public function getBaseUrl()
    {
        if (is_null($this->_baseUrl)) {
            $overrideBaseUrl = trim($this->getEnvironmentVar(self::SARLACC_URL_OVERRIDE_KEY));
            $this->_baseUrl = $overrideBaseUrl ?: self::SARLACC;
        }

        return $this->_baseUrl;
    }

    /**
     * @return string
     */
    public function getEventBaseUrl()
    {
        if (is_null($this->_eventBaseUrl)) {
            $overrideBaseUrl = trim($this->getEnvironmentVar(self::RESPONSYS_EVENT_URL_OVERRIDE_KEY));
            $this->_eventBaseUrl = $overrideBaseUrl ?: self::RESPONSYS_EVENT_URL;
        }
        return $this->_eventBaseUrl;
    }

    /**
     * @see parent
     */
    public function annotate(SourceInterface $source, $object, $action = null, $storeId = null, $context = [],
                             Registration $registration = null)
    {
        $eventData = $source->transform($object);
        if (empty($context) && isset($eventData['context'])) {
            $context = $eventData['context'];
            unset($eventData['context']);
        }
        $data = [
            'account' => $this->_account($storeId, $registration),
            'platform' => $this->_platform(),
            'action' => is_null($action) ? $source->action($object) : $action,
            'type' => $source->getEventType(),
            $source->getEventType() => $eventData
        ];
        if (!empty($context)) {
            $data['context'] = $context;
        }
        return [
            'type' => self::SARLACC_TYPE,
            'version' => self::SARLACC_VERSION,
            'data' => $data
        ];
    }

    /**
     * @see parent
     */
    public function dispatch($event)
    {
        try {
            $request = $this->_client->createRequest('POST', $this->_baseUrl)
                ->header('Content-Type', $this->_encoder->getMimeType())
                ->body($this->_encoder->encode($event));
            $response = $request->respond();
            $result = $response->code() < 300;
            if (!$result) {
                $this->_logger->info(sprintf(
                    'Unsuccessful Oracle POST: [%s] %s', $response->code(), $response->body()
                ));
                $this->_logger->debug(sprintf("Unsuccessful Oracle POST details: \n %s", var_export($request, true)));
            }
        } catch (\Exception $e) {
            $this->_logger->critical($e);
            $result = false;
        }

        return $result;
    }

    /**
     * @see parent
     */
    public function dispatchToResponsys($event)
    {
        try {
            $request = $this->_client->createRequest('POST', $this->_eventBaseUrl)
                ->header('Content-Type', $this->_encoder->getMimeType())
                ->body($this->_encoder->encode($event));
            $response = $request->respond();
            $result = $response->code() < 300;
            if (!$result) {
                $this->_logger->info(sprintf(
                    'Unsuccessful Responsys POST: [%s] %s', $response->code(), $response->body()
                ));
                $this->_logger->debug(sprintf("Unsuccessful Responsys POST details: \n %s", var_export($request, true)));
            }
        } catch (\Exception $e) {
            $this->_logger->critical($e);
            $result = false;
        }

        return $result;
    }

    /**
     * This method wraps getenv() in order to assist in unit testing
     *
     * @return string|false The environment var's value or false if not set
     */
    protected function getEnvironmentVar($varName)
    {
        return getenv($varName);
    }

    /**
     * Gets the account information for the platform
     *
     * @param Integer|Store $storeId
     * @param Registration $registration
     * @return array
     */
    protected function _account($storeId = null, Registration $registration = null)
    {
        if (!$registration && !$storeId) {
            throw new \BadFunctionCallException(
                'Could not determine account information. Neither Registration nor store ID provided');
        }

        $scope = $registration ? $registration->getScope() : 'store';
        $scopeId = $registration ? $registration->getScopeId() : $storeId;
        return [
            'siteId' => $this->_settings->getSiteId($scope, $scopeId),
            'maskId' => $this->_settings->getMaskId($scope, $scopeId)
        ];
    }

    /**
     * Gets the platform information for the platform
     *
     * @return array
     */
    private function _platform()
    {
        return [
            'id' => \Oracle\M2\Core\MetaInterface::PLATFORM_ID,
            'label' => $this->_meta->getName(),
            'version' => $this->_meta->getVersion() . ' (' . $this->_meta->getEdition() . ')'
        ];
    }

    /**
     * Gets the platform information for the platform
     *
     * @return string
     */
    public function platformVersion()
    {
        return $this->_meta->getVersion() . ' (' . $this->_meta->getEdition() . ')';
    }

    /**
     * Gets the platform information for the platform
     *
     * @return string
     */
    public function extensionVersion()
    {
        return $this->_meta->getExtensionVersion();
    }
}

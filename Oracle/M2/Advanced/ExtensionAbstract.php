<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Advanced;

abstract class ExtensionAbstract implements \Oracle\M2\Connector\Discovery\ExtensionInterface, \Oracle\M2\Connector\Discovery\GroupInterface
{
    /** @var \Oracle\M2\Core\Event\ManagerInterface */
    protected $_eventManager;

    /** @var \Oracle\M2\Connector\QueueManagerInterface */
    protected $_queueManager;

    /** @var \Oracle\M2\Connector\Event\PlatformInterface */
    protected $_platform;

    /** @var \Oracle\M2\Connector\SettingsInterface */
    protected $_connectorSettings;

    /** @var \Oracle\M2\Core\App\EmulationInterface */
    protected $_appEmulation;

    /** @var \Oracle\M2\Core\Config\FactoryInterface */
    protected $_config;

    /** @var \Oracle\M2\Connector\ConnectorInterface */
    protected $_connector;

    /** @var  \Oracle\M2\Core\Log\LoggerInterface */
    protected $logger;

    /** @var \Oracle\M2\Core\Store\ManagerInterface */
    protected $_storeManager;

    const PROCESS_KEY_SUCCESS = 'success';
    const PROCESS_KEY_ERROR = 'error';

    /**
     * @param \Oracle\M2\Core\Config\FactoryInterface $config
     * @param \Oracle\M2\Connector\ConnectorInterface $connector
     * @param \Oracle\M2\Core\Event\ManagerInterface $eventManager
     * @param \Oracle\M2\Connector\QueueManagerInterface $queueManager
     * @param \Oracle\M2\Connector\Event\PlatformInterface $platform
     * @param \Oracle\M2\Connector\SettingsInterface $connectorSettings
     * @param \Oracle\M2\Core\App\EmulationInterface $appEmulation
     * @param \Oracle\M2\Core\Log\LoggerInterface $logger
     */
    public function __construct(
        \Oracle\M2\Core\Store\ManagerInterface $storeManager,
        \Oracle\M2\Core\Config\FactoryInterface $config,
        \Oracle\M2\Connector\ConnectorInterface $connector,
        \Oracle\M2\Core\Event\ManagerInterface $eventManager,
        \Oracle\M2\Connector\QueueManagerInterface $queueManager,
        \Oracle\M2\Connector\Event\PlatformInterface $platform,
        \Oracle\M2\Connector\SettingsInterface $connectorSettings,
        \Oracle\M2\Core\App\EmulationInterface $appEmulation,
        \Oracle\M2\Core\Log\LoggerInterface $logger
    ) {
        $this->_storeManager = $storeManager;
        $this->_config = $config;
        $this->_connector = $connector;
        $this->_eventManager = $eventManager;
        $this->_queueManager = $queueManager;
        $this->_platform = $platform;
        $this->_connectorSettings = $connectorSettings;
        $this->_appEmulation = $appEmulation;
        $this->logger = $logger;
    }

    /**
     * @see parent
     */
    public function getSortOrder()
    {
        return 9000;
    }

    /**
     * @see parent
     */
    public function getEndpointId()
    {
        return 'advanced';
    }

    /**
     * @see parent
     */
    public function getEndpointName()
    {
        return 'Advanced';
    }

    /**
     * @see parent
     */
    public function getEndpointIcon()
    {
        return 'mage-icon-advanced';
    }

    /**
     * Scans connector queue for applicable flushing
     *
     * @param mixed $observer
     * @return void
     */
    public function triggerFlush($observer)
    {
        $script = $observer->getScript();
        $siteId = $script->getRegistration()->getConnectorKey();
        if ($this->_queueManager->hasItems($siteId)) {
            $script->addScheduledTask('flushEvents_new');
        }
    }

    /**
     * Sends any events or flushed processed events
     *
     * @param mixed $observer
     * @return void
     */
    public function processEvents($observer)
    {
        $script = $observer->getScript()->getObject();
        $registration = $observer->getScript()->getRegistration();
        if (array_key_exists('eventIds', $script['data'])) {
            // This is needed for backward compat
            $this->_queueManager->deleteByIds($script['data']['eventIds']);
        }
        $results = [self::PROCESS_KEY_SUCCESS => 0, self::PROCESS_KEY_ERROR => 0];
        $disabled = $this->_connectorSettings->isFlushDisabled($registration->getScope(), $registration->getScopeId()) && array_key_exists('requestId', $script['data']) && $script['data']['requestId'];
        if (!$disabled) {
            $batchSize = $this->_connectorSettings->getBatchSize($registration->getScope(), $registration->getScopeId());
            $errorHandlerSet = false;
            $start = microtime(true);
            try {
                foreach ($this->_queueManager->getOldestEvents($registration->getConnectorKey(), $batchSize) as $event) {
                    $key = self::PROCESS_KEY_ERROR;
                    try {
                        if (!$errorHandlerSet) {
                            set_error_handler(['\Oracle\M2\Core\Exception\ErrorHandler', 'handleError']);
                            $errorHandlerSet = true;
                        }
                        $data = $this->unserialize($event->getEventData());

                        if(!($this->_validateMessage($data['data']['type'],$data['data']['action'],$script['data']['objectType']))) {
                            $this->_queueManager->delete($event);
                            continue;
                        }
                        // Note: either it will be website or store.
                        $scopeName = $registration->getScope();
                        $scopeId = $registration->getScopeId();

                        $found = false;
                        if (is_array($data)) {
                            if (isset($data['data']['context']['event'])) {
                                // NOTE: For all of the object this contains one item. Then why for loop ? Have to revisit this.
                                foreach ($data['data']['context']['event'] as $type => $context) {

                                    if(isset($context['storeId'])) {
                                        $storeId = $context['storeId'];
                                        $store = $this->_storeManager->getStore($storeId);

                                        if (($scopeName == 'website' && $store->getWebsiteId() == $scopeId) || ($scopeName == 'store' && $storeId == $scopeId)) {
                                            $found = true;
                                        }
                                    }
                                    // NOTE: if record did not fit for the requested store, then no need to transform it.
                                    // continue or break? Keep continue for now.
                                    if(!$found) {
                                        continue;
                                    }
                                    $this->_appEmulation->startEnvironmentEmulation($context['storeId'], $context['area'], true);
                                    $transform = new \Oracle\M2\Common\DataObject([
                                        'context' => $context,
                                        $type => $data['data'][$type]
                                    ]);
                                    $this->_eventManager->dispatch("oracle_connector_queue_{$type}_transform", ['transform' => $transform]);
                                    $transformed = $transform->toArray();
                                    $data['data'][$type] = $transformed[$type];
                                    $data['envData'] = $script['data'];
                                    $this->_appEmulation->stopEnvironmentEmulation();
                                }
                                unset($data['data']['context']['event']);
                                if (empty($data['data']['context'])) {
                                    unset($data['data']['context']);
                                }

                                if(!$found) {
                                    continue;
                                }
                            }
                            // Note: dispatchToResponsys is new interface for the ingest request.
                            // dispatch inteface send data to the old interface.
                            // We are going to remove this to pass them to new interface.
                            if ($this->_platform->dispatchToResponsys($data)) {
                                $key = self::PROCESS_KEY_SUCCESS;
                                $this->_queueManager->delete($event);
                            }

                        } else {
                            $this->_queueManager->delete($event);
                        }
                    } catch (\Exception $e) {
                        $eMessage = sprintf("%s occurred during event processing in %s on line %d: %s", get_class($e), $e->getFile(), $e->getLine(), $e->getMessage());
                        $eEvent = isset($data['data']['context']['event']) ? $data['data']['context']['event'] : [];
                        $eEventType = isset($data['data']['type']) ? $data['data']['type'] : null;
                        $eData = [
                            'type' => $eEventType,
                            'action' => isset($data['data']['action']) ? $data['data']['action'] : null,
                            'storeId' => ($eEventType && isset($eEvent[$eEventType]['storeId']))
                                    ? $eEvent[$eEventType]['storeId']
                                    : null,
                            'typeId' => ($eEventType && isset($eEvent[$eEventType]['id']))
                                    ? $eEvent[$eEventType]['id']
                                    : null
                        ];
                        $this->logger->critical($eMessage, $eData);
                        $key = self::PROCESS_KEY_ERROR;
                        $this->_queueManager->delete($event);
                    }
                    $results[$key]++;
                }
                $time_elapsed_secs = microtime(true) - $start;
                $this->logger->info("Time taken to flush the event ". $time_elapsed_secs);
            } finally {
                if ($errorHandlerSet) {
                    restore_error_handler();
                }
            }
        }
        $observer->getScript()->setProgress($results);
    }

    protected function _validateMessage($dataType, $dataAction, $objectType)
    {
        if($objectType == 'all')
            return true;
        if($dataType=='order' && $dataAction=='add' && in_array("order_confirmation",$objectType))
            return true;
        if($dataType=='order' && $dataAction=='delete' && in_array("order_cancellation",$objectType))
            return true;
        if($dataType=='contact' && $dataAction=='add' && (in_array("customer_account_welcome",$objectType) || in_array("new_account_without_password", $objectType)))
            return true;
        if($dataType=='cart' && in_array("cart_event",$objectType))
            return true;
        return false;
    }

    /**
     * Forwards any historical import to the module
     *
     * @param mixed $observer
     * @return void
     */
    public function processHistorical($observer)
    {
        // oracle_connector_script_contact_historical
        $script = $observer->getScript()->getObject();
        $eventAreaName = "oracle_connector_script_{$script['data']['type']}_historical";
        $this->_eventManager->dispatch($eventAreaName, [
            'script' => $observer->getScript()
        ]);
    }

    /**
     * Forwards any test imports to the handling module
     *
     * @param mixed $observer
     * @return void
     */
    public function processTest($observer)
    {
        $script = $observer->getScript()->getObject();
        $eventAreaName = "oracle_connector_script_{$script['data']['type']}_test";
        $this->_eventManager->dispatch($eventAreaName, [
            'script' => $observer->getScript()
        ]);
    }

    /**
     * Passes along all of the configured settings
     *
     * @param mixed $observer
     * @return void
     */
    public function previewSettings($observer)
    {
        $registration = $observer->getScript()->getRegistration();
        $script = $observer->getScript()->getObject();
        $prefix = 'oracle/';
        if ($script['data']['moduleSettings'] != 'all') {
            $prefix .= $script['data']['moduleSettings'] . '/';
        }
        $scopeTree = $this->_connector->scopeTree($registration);
        $observer->getScript()->setResults($this->_walkSettings($scopeTree, $prefix));
    }

    /**
     * Walk the registration tree for configured settings
     *
     * @param array $scopeTree
     * @param string $prefix
     * @param array $results
     * @return array
     */
    protected function _walkSettings($scopeTree, $prefix, $results = [])
    {
        $childSettings = [];
        list($scopeName, $scopeId) = explode('.', $scopeTree['id']);
        if ($scopeName != 'default') {
            $scopeName .= 's';
        }
        $data = $this->_config->getCollection()
            ->addFieldToFilter('path', ['like' => $prefix . '%'])
            ->addFieldToFilter('scope', ['eq' => $scopeName])
            ->addFieldToFilter('scope_id', ['eq' => $scopeId]);
        foreach ($data as $config) {
            $pathParts = explode('/', $config->getPath());
            if (count($pathParts) < 5) {
                continue;
            }
            $collection =& $childSettings;
            foreach (array_slice($pathParts, 1, count($pathParts) - 2) as $part) {
                if (!array_key_exists($part, $collection)) {
                    $collection[$part] = [];
                }
                $collection =& $collection[$part];
            }
            $value = $config->getValue();
            if (preg_match('|/objects/|', $config->getPath())) {
                $value = @unserialize($value);
                if ($value === false) {
                    $value = $config->getValue();
                }
            }
            $collection[end($pathParts)] = $value;
        }
        if (!empty($childSettings)) {
            $results[] = [
                'context' => [
                    'name' => $scopeTree['name'],
                    'scope' => $scopeName,
                    'settings' => $childSettings
                ]
            ];
        }
        foreach ($scopeTree['children'] as $childScope) {
            $results = $this->_walkSettings($childScope, $prefix, $results);
        }
        return $results;
    }

    /**
     * @see parent
     */
    public function gatherEndpoints($observer)
    {
        $observer->getDiscovery()->addGroupHelper($this);
    }

    /**
     * @see parent
     */
    public function endpointInfo($observer)
    {
        $observer->getEndpoint()->addExtension([
            'id' => 'testImport',
            'name' => 'Test Mode',
            'fields' => [
                [
                    'id' => 'enabled',
                    'name' => 'Pause Event Queuing',
                    'type' => 'boolean',
                    'required' => true,
                    'typeProperties' => [
                        'default' => $observer->getRegistration()->getEnvironment() == 'SANDBOX'
                    ]
                ],
                [
                    'id' => 'disableFlush',
                    'name' => 'Pause Event Processing',
                    'type' => 'boolean',
                    'required' => true,
                    'allowedScopes' => [ $observer->getRegistration()->getScopeHash() ],
                    'depends' => [
                        [ 'id' => 'enabled', 'values' => [ false ]]
                    ],
                    'typeProperties' => [
                        'default' => false
                    ]
                ]
            ]
        ]);

        $batchSizeOptions = [];
        foreach ([10, 25, 50, 100, 200] as $number) {
            $batchSizeOptions[] = ['id' => $number, 'name' => $number];
        }

        /*
         * The integer values in the proceeding array represent the desired inter-operation
         * delay, in seconds, to be used when calling a given Magento instance.  The result
         * of dividing each array member by 60 results in the maximum number of requests that
         * could be made in a minute.
         *
         * Delay (seconds) ==> Display Name
         *  0 ==> "No Limit"
         *  1 ==> "60 reqs/min"
         *  2 ==> "30 reqs/min"
         *  3 ==> "20 reqs/min"
         *
         */
        $requestDelayOptions = [];
        foreach ([0, 1, 2, 3] as $number) {
            $name = 'No Limit';
            if ($number != 0) {
                $reqPerMinute = 60 / $number;
                $name = "$reqPerMinute reqs/min";
            }
            $requestDelayOptions[] = ['id' => $number, 'name' => $name];
        }

        $observer->getEndpoint()->addExtension([
            'id' => 'performance',
            'name' => 'Performance',
            'fields' => [
                [
                    'id' => 'batchSize',
                    'name' => 'Import Batch Size',
                    'required' => true,
                    'type' => 'select',
                    'typeProperties' => [
                        'options' => $batchSizeOptions,
                        'default' => 25
                    ]
                ],
                [
                    'id' => 'requestDelay',
                    'name' => 'Maximum requests per minute',
                    'required' => true,
                    'type' => 'select',
                    'typeProperties' => [
                        'options' => $requestDelayOptions,
                        'default' => 0
                    ]
                ]
            ]
        ]);

        $defaultFlush = 'flushEvents_new';
        $observer->getEndpoint()->addScript([
            'id' => 'event',
            'name' => 'Schedule Task',
            'url' => 'oracle/connector/script',
            'fields' => [
                [
                    'id' => 'jobName',
                    'name' => 'Type',
                    'type' => 'select',
                    'typeProperties' => [
                        'options' => [
                            [
                                'id' => $defaultFlush,
                                'name' => 'Process Import Queue'
                            ],
                            [
                                'id' => 'previewSettings',
                                'name' => 'Preview Configured Settings'
                            ]
                        ],
                        'default' => $defaultFlush
                    ]
                ],
                [
                    'id' => 'moduleSettings',
                    'name' => 'Module',
                    'type' => 'select',
                    'depends' => [
                        [ 'id' => 'jobName', 'values' => [ 'previewSettings' ] ]
                    ],
                    'typeProperties' => [
                        'default' => 'all',
                        'options' => [
                            [ 'id' => 'all', 'name' => 'All Modules' ]
                        ]
                    ]
                ]
            ]
        ]);

        $observer->getEndpoint()->addScript([
            'id' => 'historical',
            'name' => 'Add to Import Queue',
            'url' => 'oracle/connector/script',
            'fields' => [
                [
                    'id' => 'jobName',
                    'name' => 'Type',
                    'type' => 'select',
                    'position' => 0,
                    'typeProperties' => [
                        'options' => []
                    ]
                ],
                [
                    'id' => 'startTime',
                    'name' => 'Created After',
                    'type' => 'date',
                    'position' => 10
                ],
                [
                    'id' => 'endTime',
                    'name' => 'Created Before',
                    'type' => 'date',
                    'position' => 20
                ]
            ]
        ]);

        $observer->getEndpoint()->addScript([
            'id' => 'test',
            'name' => 'Test Import',
            'url' => 'oracle/connector/script',
            'fields' => [
                [
                    'id' => 'jobName',
                    'name' => 'Type',
                    'type' => 'select',
                    'position' => 0,
                    'typeProperties' => [
                        'options' => []
                    ]
                ],
                [
                    'id' => 'performImport',
                    'name' => 'Import to Oracle',
                    'type' => 'boolean',
                    'position' => 20,
                    'typeProperties' => [ 'default' => false ]
                ],
            ]
        ]);
    }

    /**
     * Note: This utility function is designed to be used by ExtensionAbstract::unserialize().
     * It will not provide any index checks prior to referencing them as to not over-complicate
     * the logic
     *
     * @param array $match
     * @return string
     */
    public static function replaceMatches(array $match)
    {
        return ($match[1] != strlen($match[2])) ? 's:' . strlen($match[2]) . ':"' . $match[2] . '";' : $match[0];
    }

    /**
     * Performs a safe unserialize of a poorly serialized string due to the conversion of multi-byte
     * special characters to single-byte '?' characters at the time of writing to the database and not
     * updating the string length in its byte-stream representation.
     *
     * This will NOT fully repair the poorly converted string. It will repair it enough to make the
     * string unserializeable. In other words, it will not recover the lost converted multi-byte characters
     *
     * @param string $eventData
     * @return mixed|null The unserialized event or null if unserialization was unsuccessful
     */
    private function unserialize($eventData)
    {
        // Fix any broken serialized string
        $eventData = preg_replace_callback(
            '/s:(\d+):\"(.*?)\";/',
            [$this, 'replaceMatches'],
            $eventData
        );

        return @unserialize($eventData);
    }
}

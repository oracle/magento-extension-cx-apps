<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Connector\Discovery;

use Oracle\Connector\Model\Registration;
use \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection as ResourceModelCollection;
use \Magento\Eav\Model\Entity\Collection\AbstractCollection as EavModelCollection;

abstract class AdvancedExtensionAbstract extends ExtensionPushEventAbstract implements AdvancedExtensionInterface
{
    /** @var \Oracle\M2\Core\App\EmulationInterface */
    protected $_appEmulation;

    /** @var \Psr\Log\LoggerInterface */
    protected $logger;

    /** @var \Magento\Framework\Filesystem\DriverInterface */
    protected $fileSystemDriver;

    /**
     * @param \Oracle\M2\Core\Store\ManagerInterface $storeManager
     * @param \Oracle\M2\Connector\QueueManagerInterface $queueManager
     * @param \Oracle\M2\Connector\SettingsInterface $connectorSettings
     * @param \Oracle\M2\Connector\Event\HelperInterface $helper
     * @param \Oracle\M2\Connector\Event\PlatformInterface $platform
     * @param \Oracle\M2\Connector\Event\SourceInterface $source
     * @param \Magento\Framework\Filesystem\DriverInterface $fileSystemDriver
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Oracle\M2\Core\App\EmulationInterface $appEmulation,
        \Oracle\M2\Core\Store\ManagerInterface $storeManager,
        \Oracle\M2\Connector\QueueManagerInterface $queueManager,
        \Oracle\M2\Connector\SettingsInterface $connectorSettings,
        \Oracle\M2\Connector\Event\HelperInterface $helper,
        \Oracle\M2\Connector\Event\PlatformInterface $platform,
        \Oracle\M2\Connector\Event\SourceInterface $source,
        \Magento\Framework\Filesystem\DriverInterface $fileSystemDriver,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::__construct(
            $storeManager,
            $queueManager,
            $connectorSettings,
            $helper,
            $platform,
            $source
        );
        $this->_appEmulation = $appEmulation;
        $this->fileSystemDriver = $fileSystemDriver;
        $this->logger = $logger;
    }

    /**
     * Performs a test import based on user defined input
     *
     * @param mixed $observer
     * @return void
     */
    public function testImport($observer)
    {
        /* @var Registration $registration */
        $registration = $observer->getScript()->getRegistration();
        $script = $observer->getScript()->getObject();
        $events = [];
        $objects = $this->_sendTest($registration, $script['data']);
        $source = $this->_source($script['data']);
        foreach ($objects as $object) {
            $this->_appEmulation->startEnvironmentEmulation($object->getStoreId(), 'frontend', true);
            $action = $source->action($object);
            $action = empty($action) ? 'add' : $action;
            $event = $this->_platform->annotate($source, $object, $action, $object->getStoreId(), null, $registration);
            $this->_appEmulation->stopEnvironmentEmulation();
            if (array_key_exists('performImport', $script['data']) && $script['data']['performImport']) {
                $this->_platform->dispatch($event);
            }
            $events[] = $event['data'];
        }
        $observer->getScript()->setResults($events);
    }

    /**
     * Performs a historical import based on user defined input
     *
     * @param mixed $observer
     * @return void
     */
    public function historicalImport($observer)
    {
        $registration = $observer->getScript()->getRegistration();
        $script = $observer->getScript()->getObject();
        $numbers = [
            'processed' => 0,
            'success' => 0,
            'error' => 0,
            'skipped' => 0
        ];
        $objects = $this->_sendHistorical($registration, $script['data']);
        $source  = $this->_source($script['data']);
        // It checks if the class method exists in the given object.
        if (method_exists($objects, 'getSelectSql')) {
            // getSelectSql is the sql statement for the collection. Here its about object.
            $this->logger->info('Historical import select query: ' . $objects->getSelectSql(true));
        }
        $events["objectData"] = [];
        // inside _limitOffset we make offset and limit call to db
        foreach ($this->_limitOffset($objects, $script['data']) as $object) {
            $action = $this->_historicalAction($script['data'], $object);
            if (!empty($action)) {
                $this->_appEmulation->startEnvironmentEmulation($object->getStoreId(), 'frontend', true);
                // Any code written here after startEnvironmentEmulation, will execute the code in the specified store.
                // It means it will add the design, template and all the store relevant data to this piece of code.
                $store = $this->_storeManager->getStore($object->getStoreId());
                // annotate means transfor the object acconrdingly.
                $event = $this->_platform->annotate($source, $object, $action, $store);
                $this->_appEmulation->stopEnvironmentEmulation();

                $events["objectData"][] = $event;
            } else {
                $numbers['skipped']++;
            }
            $numbers['processed']++;
        }
        $page = 1;
        if (array_key_exists('page', $script['data'])) {
            $page = $script["data"]['page'];
        }
        $events["pageIndex"]  = $page;
        if (array_key_exists('options', $script['data'])) {
            $events["envData"] = $script["data"]["options"];
        }
        if (array_key_exists('startTime', $script['data'])) {
            $events["startTime"] = $script['data']['startTime'];
        }
        if (array_key_exists('endTime', $script['data'])) {
            $events["endTime"] = $script['data']['endTime'];
        }

        if (array_key_exists('processedCount', $script['data'])) {
            $events["count"]["processedCount"] = $script['data']['processedCount'];
        }
        if (array_key_exists('errorCount', $script['data'])) {
            $events["count"]["errorCount"] = $script['data']['errorCount'];
        }
        if (array_key_exists('successCount', $script['data'])) {
            $events["count"]["successCount"] = $script['data']['successCount'];
        }

        $events["envData"]["platformVersion"] = $this->_platform->platformVersion();

        $events["envData"]["extensionVersion"] = $this->_platform->extensionVersion();

        $accountId =  base64_encode($script['data']['options']['installId'].'__'.$script['data']['options']['tenant']);
        $secret = $script["data"]["options"]["secret"];
        $hashed_value = base64_encode(hash_hmac('sha256', $accountId, $secret,true));
        $events["envData"]["hashedValue"] = $hashed_value;

        if (!empty($events) && $this->_platform->dispatch($events)) {
            $numbers['success']++;
        } else {
            $numbers['error']++;
        }

        $script['data']['size'] = $numbers['processed'];
        if (empty($script['data']['options'])) {
            unset($script['data']['options']);
        }
        $results = ['results' => $observer->getScript()->createProgresses($numbers)];
        $observer->getScript()->setResults(array_merge($script['data'], $results));
    }

    /**
     * @param string $extensionName Otherwise known as the module name
     * @return []
     */
    protected function getAutoConfigData($extensionName)
    {
        // This is the file to get the autoconfig data.
        // $extensionName can be contact, intergartion, optin, order, product.

        $autoConfigDir = __DIR__ . "/AutoConfig";
        $autoConfigFile = $this->fileSystemDriver->getRealPath("{$autoConfigDir}/{$extensionName}.json");
        if (!$this->fileSystemDriver->isReadable($autoConfigFile)) {
            $this->logger->debug("AutoConfig file does not exist or cannot be read for the {$extensionName} extension");
            return [];
        }
        return json_decode($this->fileSystemDriver->fileGetContents($autoConfigFile));
    }

    /**
     * Applies the limit and offset rules for input data
     *
     * @param \Iterator $objects
     * @param array $data
     * @return \Iterator
     */
    protected function _limitOffset($objects, $data)
    {
        $page = 1;
        if (array_key_exists('page', $data)) {
            $page = $data['page'];
        }
        $perPage = 20;
        if (array_key_exists('size', $data)) {
            $perPage = $data['size'];
        }
        return $this->_applyLimitOffset($objects, $perPage, $page);
    }

    /**
     * Applies the LIMIT and OFFSET to the appropriate collection
     *
     * @param mixed $objects
     * @param int $limit
     * @param int $offset
     * @return mixed
     */
    protected function _applyLimitOffset($objects, $limit, $offset)
    {
        if (method_exists($objects, 'getSelectSql')) {
            $this->logger->info('select query again: ' . $objects->getSelectSql(true) . '$offset = '.$offset. '$limit = '.$limit);
        }

        $objects->getSelect()->limitPage($offset, $limit);
        return $objects;
    }

    /**
     * Intended to allow for implementors to override the default action
     *
     * @param mixed $data
     * @param mixed $object
     * @return string
     */
    protected function _historicalAction($data, $object)
    {
        return $this->_source($data)->action($object);
    }

    /**
     * Intended to swap out the source if duality is used
     *
     * @param array $data
     * @return \Oracle\M2\Connector\Event\SourceInterface
     */
    protected function _source($data)
    {
        return $this->_source;
    }

    /**
     * Implementors would return a collection that satisfies the constraints
     * supplied in as the data array
     *
     * @param \Oracle\M2\Connector\RegistrationInterface $registration
     * @param array $data
     * @return \Iterator
     */
    abstract protected function _sendTest($registration, $data);

    /**
     * Implementors would return a collection that satisfies the contraints
     * supplied in as the data array
     *
     * @param \Oracle\M2\Connector\RegistrationInterface $registration
     * @param array $data
     * @return ResourceModelCollection|EavModelCollection|\Iterator|[]
     */
    abstract protected function _sendHistorical($registration, $data);
}

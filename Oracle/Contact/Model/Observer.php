<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Contact\Model;

class Observer extends \Oracle\M2\Contact\ExtensionAbstract
{
    /** @var \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory  */
    protected $_customerData;

    /** @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory */
    protected $_orderData;

    /**
     * @param \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerData
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderData
     * @param \Oracle\M2\Core\Sales\OrderCacheInterface $orderRepo
     * @param \Oracle\M2\Core\Customer\CacheInterface $customerRepo
     * @param \Oracle\M2\Core\App\EmulationInterface $appEmulation
     * @param \Oracle\M2\Core\Store\ManagerInterface $storeManager
     * @param \Oracle\M2\Connector\QueueManagerInterface $queueManager
     * @param \Oracle\M2\Connector\SettingsInterface $connectorSettings
     * @param \Oracle\M2\Connector\Event\PlatformInterface $platform
     * @param \Oracle\M2\Contact\Event\Source $source
     * @param \Oracle\M2\Contact\SettingsInterface $helper
     * @param \Magento\Framework\Filesystem\DriverInterface $fileSystemDriver
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerData,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderData,
        \Oracle\M2\Core\Sales\OrderCacheInterface $orderRepo,
        \Oracle\M2\Core\Customer\CacheInterface $customerRepo,
        \Oracle\M2\Core\App\EmulationInterface $appEmulation,
        \Oracle\M2\Core\Store\ManagerInterface $storeManager,
        \Oracle\M2\Connector\QueueManagerInterface $queueManager,
        \Oracle\M2\Connector\SettingsInterface $connectorSettings,
        \Oracle\M2\Connector\Event\PlatformInterface $platform,
        \Oracle\M2\Contact\Event\Source $source,
        \Oracle\M2\Contact\SettingsInterface $helper,
        \Magento\Framework\Filesystem\DriverInterface $fileSystemDriver,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::__construct(
            $orderRepo,
            $customerRepo,
            $appEmulation,
            $storeManager,
            $queueManager,
            $connectorSettings,
            $helper,
            $platform,
            $source,
            $fileSystemDriver,
            $logger
        );
        $this->_customerData = $customerData;
        $this->_orderData = $orderData;
    }

    /**
     * @see parent
     */
    public function translate($message)
    {
        return __($message);
    }

    /**
     * @see parent
     * @return \Magento\Customer\Model\ResourceModel\Customer\Collection
     */
    protected function _contactCollection()
    {
       // $this->_customerData->create() to create the model object.
        return $this->_customerData->create();
    }

    /**
     * @see parent
     * @return \Magento\Sales\Model\ResourceModel\Order\Collection
     */
    protected function _orderCollection()
    {
        return $this->_orderData->create();
    }
}

<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Product\Model;

class Observer extends \Oracle\M2\Product\ExtensionAbstract
{
    /** @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory */
    protected $_productsFactory;

    /**
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productsFactory
     * @param \Oracle\M2\Core\Catalog\ProductAttributeCacheInterface $attributes
     * @param \Oracle\M2\Connector\MiddlewareInterface $middleware
     * @param \Oracle\M2\Connector\RegistrationManagerInterface $registrations
     * @param \Oracle\M2\Core\Catalog\ProductCacheInterface $productRepo
     * @param \Oracle\M2\Core\App\EmulationInterface $appEmulation
     * @param \Oracle\M2\Core\Store\ManagerInterface $storeManager
     * @param \Oracle\M2\Connector\QueueManagerInterface $queueManager
     * @param \Oracle\M2\Connector\SettingsInterface $connectorSettings
     * @param \Oracle\M2\Connector\Event\HelperInterface $helper
     * @param \Oracle\M2\Connector\Event\PlatformInterface $platform
     * @param \Oracle\M2\Product\Event\Source $source
     * @param \Magento\Framework\Filesystem\DriverInterface $fileSystemDriver
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productsFactory,
        \Oracle\M2\Core\Catalog\ProductAttributeCacheInterface $attributes,
        \Oracle\M2\Connector\MiddlewareInterface $middleware,
        \Oracle\M2\Connector\RegistrationManagerInterface $registrations,
        \Oracle\M2\Core\Catalog\ProductCacheInterface $productRepo,
        \Oracle\M2\Core\App\EmulationInterface $appEmulation,
        \Oracle\M2\Core\Store\ManagerInterface $storeManager,
        \Oracle\M2\Connector\QueueManagerInterface $queueManager,
        \Oracle\M2\Connector\SettingsInterface $connectorSettings,
        \Oracle\M2\Product\SettingsInterface $helper,
        \Oracle\M2\Connector\Event\PlatformInterface $platform,
        \Oracle\M2\Product\Event\Source $source,
        \Magento\Framework\Filesystem\DriverInterface $fileSystemDriver,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::__construct(
            $attributes,
            $middleware,
            $registrations,
            $productRepo,
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
        $this->_productsFactory = $productsFactory;
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
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    protected function _collection()
    {
        return $this->_productsFactory->create();
    }
}

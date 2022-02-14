<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Order\Model;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\OrderRepositoryInterface;

class Observer extends \Oracle\M2\Order\ExtensionAbstract
{
    /** @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory */
    protected $_orderData;

    /**
     * @param \Oracle\M2\Core\Catalog\ProductAttributeCacheInterface $attributes
     * @param \Oracle\M2\Core\Sales\OrderStatusesInterface $statuses
     * @param \Oracle\M2\Core\Sales\OrderCacheInterface $orderRepo
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderData
     * @param \Oracle\M2\Core\App\EmulationInterface $appEmulation
     * @param \Oracle\M2\Core\Store\ManagerInterface $storeManager
     * @param \Oracle\M2\Connector\QueueManagerInterface $queueManager
     * @param \Oracle\M2\Connector\SettingsInterface $connectorSettings
     * @param \Oracle\M2\Connector\Event\PlatformInterface $platform
     * @param \Oracle\M2\Order\Event\Source $source
     * @param \Oracle\M2\Order\SettingsInterface $helper
     * @param \Oracle\M2\Helper\Data $mageHelper
     * @param SearchCriteriaBuilder $criteriaBuilder
     * @param FilterBuilder $filterBuilder
     * @param FilterGroupBuilder $filterGroupBuilder
     * @param OrderRepositoryInterface $orderRepository
     * @param \Magento\Framework\Filesystem\DriverInterface $fileSystemDriver
     * @param LoggerInterface $logger
     */
    public function __construct(
        \Oracle\M2\Core\Catalog\ProductAttributeCacheInterface $attributes,
        \Oracle\M2\Core\Sales\OrderStatusesInterface $statuses,
        \Oracle\M2\Core\Sales\OrderCacheInterface $orderRepo,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderData,
        \Oracle\M2\Core\App\EmulationInterface $appEmulation,
        \Oracle\M2\Core\Store\ManagerInterface $storeManager,
        \Oracle\M2\Connector\QueueManagerInterface $queueManager,
        \Oracle\M2\Connector\SettingsInterface $connectorSettings,
        \Oracle\M2\Connector\Event\PlatformInterface $platform,
        \Oracle\M2\Order\Event\Source $source,
        \Oracle\M2\Order\SettingsInterface $helper,
        \Oracle\M2\Helper\Data $mageHelper,
        SearchCriteriaBuilder $criteriaBuilder,
        FilterBuilder $filterBuilder,
        FilterGroupBuilder $filterGroupBuilder,
        OrderRepositoryInterface $orderRepository,
        \Magento\Framework\Filesystem\DriverInterface $fileSystemDriver,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::__construct(
            $attributes,
            $statuses,
            $orderRepo,
            $appEmulation,
            $storeManager,
            $queueManager,
            $connectorSettings,
            $helper,
            $platform,
            $source,
            $mageHelper,
            $criteriaBuilder,
            $filterBuilder,
            $filterGroupBuilder,
            $orderRepository,
            $fileSystemDriver,
            $logger
        );
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
     */
    protected function _collection()
    {
        return $this->_orderData->create();
    }
}

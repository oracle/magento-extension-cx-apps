<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Order\Plugin;

use Oracle\Connector\Api\Data\TidInterface;
use Oracle\Connector\Api\TidRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\OrderRepository;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use Magento\Sales\Api\Data\OrderExtensionFactory;

/**
 * Class GetList
 * @package Oracle\Order\Plugin
 */
class GetList
{
    /** @var TidRepositoryInterface */
    protected $tidRepo;

    /** @var OrderExtensionFactory */
    protected $orderExtensionFactory;

    /**
     * GetList constructor.
     * @param TidRepositoryInterface $tidRepo
     * @param OrderExtensionFactory $orderExtensionFactory
     */
    public function __construct(
        TidRepositoryInterface $tidRepo,
        OrderExtensionFactory $orderExtensionFactory
    ) {
        $this->tidRepo = $tidRepo;
        $this->orderExtensionFactory = $orderExtensionFactory;
    }

    /**
     * @param OrderRepository $orderRepo
     * @param Collection $orderCollection [null]
     * @return Collection|null
     */
    public function afterGetList(OrderRepository $orderRepo, Collection $orderCollection = null)
    {
        if (!$orderCollection) {
            return $orderCollection;
        }
        return $this->attachTids($orderRepo, $orderCollection);
    }

    /**
     * @param OrderRepository $orderRepo
     * @param Collection $orderCollection
     * @return Collection
     */
    public function attachTids(OrderRepository $orderRepo, Collection $orderCollection)
    {
        $orderIds = [];
        /** @var OrderInterface $order */
        foreach ($orderCollection->getItems() as $order) {
            $orderIds[] = $order->getEntityId();
        }
        if (empty($orderIds)) {
            return $orderCollection;
        }
        $tids = $this->tidRepo->getByOrderIds($orderIds);
        /** @var TidInterface $tid */
        foreach ($tids->getItems() as $tid) {
            $order = $orderCollection->getItemById($tid->getOrderId());
            $extensionAttributes = $order->getExtensionAttributes() ?: $this->orderExtensionFactory->create();
            $extensionAttributes->setOracleTid($tid);
        }
        return $orderCollection;
    }
}
<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Connector\Model;


use Oracle\Connector\Api\Data\TidInterface;
use Oracle\Connector\Api\TidRepositoryInterface;
use Oracle\Connector\Model\ResourceModel\Tid\Collection;
use Oracle\Connector\Model\ResourceModel\Tid\CollectionFactory;
use Oracle\Connector\Model\Spi\TidResourceInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Zend\Loader\Exception\DomainException;

/**
 * Class TidRepository
 *
 * @package Oracle\Connector\Model
 */
class TidRepository implements TidRepositoryInterface
{
    /** @var TidFactory */
    protected $tidFactory;

    /** @var TidResourceInterface */
    protected $resourceModel;

    /** @var TidCollectionFactory */
    protected $collectionFactory;

    /**
     * TidRepository constructor.
     * @param TidFactory $tidFactory
     * @param TidResourceInterface $resourceModel
     */
    public function __construct(
        TidFactory $tidFactory,
        TidResourceInterface $resourceModel,
        CollectionFactory $collectionFactory
    ) {
        $this->tidFactory = $tidFactory;
        $this->resourceModel = $resourceModel;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @param string $value
     * @param int $cartId
     * @return Tid
     */
    public function create($value, $cartId)
    {
        return $this->tidFactory->create()->setValue($value)->setCartId($cartId);
    }
    
    /**
     * @param TidInterface $tid
     * @throws LocalizedException if Cart ID isn't set
     */
    public function save(TidInterface $tid)
    {
        try {
            if (!$tid->getCartId()) {
                throw new LocalizedException(new Phrase('Cart ID is not set'));
            }
            if (!$tid->getCreatedAt()) {
                $tid->setCreatedAt(time());
            }
        } catch (\Exception $e) {
            throw new LocalizedException(new Phrase('Error occurred when saving tid: ' . $e->getMessage()), $e);
        }

        $this->resourceModel->save($tid);
        return $tid;
    }

    /**
     * Creates a new tid model if one isn't found
     *
     * @param int $cartId
     * @return Tid|null
     */
    public function getByCartId($cartId)
    {
        /** @var Collection $collection */
        $collection = $this->collectionFactory->create()->addCartIdToFilter($cartId);
        return $collection->count() ? $collection->getFirstItem() : null;
    }

    /**
     * @param int $orderId
     * @return Tid|null
     */
    public function getByOrderId($orderId)
    {
        /** @var Collection $collection */
        $collection = $this->collectionFactory->create()->addOrderIdToFilter($orderId);
        return $collection->count() ? $collection->getFirstItem() : null;
    }

    /**
     * @param int[] $orderIds
     * @return Collection
     */
    public function getByOrderIds(array $orderIds)
    {
        return $this->collectionFactory->create()->addOrderIdsToFilter($orderIds);
    }

    /**
     * @param TidInterface $tid
     * @return self
     * @throws \Exception on failure to delete 
     */
    public function delete(TidInterface $tid)
    {
        return $this->resourceModel->delete($tid);
    }
}
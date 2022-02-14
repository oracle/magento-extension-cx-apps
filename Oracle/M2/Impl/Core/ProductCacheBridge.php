<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Impl\Core;

class ProductCacheBridge extends \Oracle\M2\Core\Catalog\ProductCacheAbstract
{
    protected $_repo;
    protected $_typePool;
    protected $_catalogType;
    protected $_logger;
    protected $_searchBuilder;

    /**
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $repo
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchBuilder
     * @param \Oracle\M2\Core\Catalog\ImageHelperInterface $imageHelper
     * @param \Oracle\M2\Core\Store\ManagerInterface $storeManager
     * @param \Oracle\M2\Core\Catalog\ProductCategoryResolverInterface $resolver
     * @param \Magento\Catalog\Model\Product\Type\Pool $typePool
     * @param \Magento\Catalog\Model\Product\Type $catalogType
     * @param \Oracle\M2\Core\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Catalog\Api\ProductRepositoryInterface $repo,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchBuilder,
        \Oracle\M2\Core\Catalog\ImageHelperInterface $imageHelper,
        \Oracle\M2\Core\Store\ManagerInterface $storeManager,
        \Oracle\M2\Core\Catalog\ProductCategoryResolverInterface $resolver,
        \Magento\Catalog\Model\Product\Type\Pool $typePool,
        \Magento\Catalog\Model\Product\Type $catalogType,
        \Oracle\M2\Core\Log\LoggerInterface $logger,
        \Magento\Framework\Url $frontUrlModel
    ) {
        parent::__construct($imageHelper, $storeManager, $resolver, $frontUrlModel);
        $this->_repo = $repo;
        $this->_searchBuilder = $searchBuilder;
        $this->_typePool = $typePool;
        $this->_catalogType = $catalogType;
        $this->_logger = $logger;
    }

    /**
     * @see parent
     */
    public function getById($productId, $storeId = null)
    {
        try {
            return $this->_repo->getById($productId, false, $storeId);
        } catch (\Exception $e) {
            $this->_logger->critical($e);
            return null;
        }
    }

    /**
     * @see parent
     */
    public function getBySku($productSku, $storeId = null)
    {
        try {
            return $this->_repo->get($productSku, false, $storeId);
        } catch (\Exception $e) {
            $this->_logger->critical($e);
            return null;
        }
    }

    /**
     * @see parent
     */
    public function getBySource(\Oracle\M2\Connector\Discovery\Source $source)
    {
        $filters = $source->getFilters();
        if (array_key_exists('sku', $filters)) {
            $this->_searchBuilder->addFilter('sku', "%{$filters['sku']}", 'like');
        }
        if (array_key_exists('name', $filters)) {
            $this->_searchBuilder->addFilter('name', "%{$filters['name']}%", 'like');
        }
        if ($source->getId()) {
            $this->_searchBuilder->addFilter('entity_id', $source->getId(), 'eq');
        }
        $this->_searchBuilder
            ->setPageSize($source->getLimit())
            ->setCurrentPage($source->getCurrentPage());
        return $this->_repo->getList($this->_searchBuilder->create())->getItems();
    }

    /**
     * @see parent
     */
    public function getChildrenIds($productId, $storeId = null)
    {
        $product = $productId;
        if (is_numeric($productId)) {
            $product = $this->getById($productId, $storeId);
        }
        if (is_null($product) || $product->getTypeId() == 'simple') {
            return [];
        }
        $childrenIds = $product
            ->getTypeInstance()
            ->getChildrenIds($product->getId(), true);
        if (!empty($childrenIds)) {
            return $childrenIds[0];
        }
        return $childrenIds;
    }

    /**
     * @see parent
     */
    public function getParent($productId, $storeId = null)
    {
        $product = $productId;
        if (is_numeric($productId)) {
            $product = $this->getById($productId, $storeId);
        }
        if (is_null($product) || $product->isComposite()) {
            return null;
        }
        // This was largely copied from type, except filtering by
        // compposite types
        $composites = $this->_catalogType->getCompositeTypes();
        $allTypes = $this->_catalogType->getTypes();
        foreach ($composites as $typeId) {
            if (!empty($allTypes[$typeId]['model'])) {
                $instance = $allTypes[$typeId]['model'];
                $typeModel = $this->_typePool->get($instance);
                $typeModel->setConfig($allTypes[$typeId]);
                $parents = $typeModel->getParentIdsByChild($product->getId());
                if (empty($parents) || !isset($parents[0])) {
                    continue;
                }
                $parent = $this->getById($parents[0], $product->getStoreId());
                if ($parent) {
                    return $parent;
                }
            }
        }
        return null;
    }
}

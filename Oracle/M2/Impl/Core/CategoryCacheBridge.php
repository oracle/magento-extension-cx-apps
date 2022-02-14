<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Impl\Core;

class CategoryCacheBridge implements \Oracle\M2\Core\Catalog\CategoryCacheInterface
{
    protected $_repo;
    protected $_dataFactory;

    /**
     * @param \Magento\Catalog\Api\CategoryRepositoryInterface $repo
     * @param \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $dataFactory
     */
    public function __construct(
        \Magento\Catalog\Api\CategoryRepositoryInterface $repo,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $dataFactory
    ) {
        $this->_repo = $repo;
        $this->_dataFactory = $dataFactory;
    }

    /**
     * @see parent
     */
    public function getById($categoryId, $storeId = null)
    {
        return $this->_repo->get($categoryId, $storeId);
    }

    /**
     * @see parent
     */
    public function getBySource(\Oracle\M2\Connector\Discovery\Source $source)
    {
        $categories = $this->_dataFactory->create()->addNameToResult();
        $filters = $source->getFilters();
        if (array_key_exists('name', $filters)) {
            $categories->addAttributeToFilter('name', [ 'like' => "%{$filters['name']}%" ]);
        }
        if ($source->getId()) {
            $categories->addFieldToFilter('entity_id', [ 'eq' => $source->getId() ]);
        }
        $categories->getSelect()->limitPage($source->getOffset(), $source->getLimit());
        return $categories;
    }
}

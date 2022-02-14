<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Impl\Core;

class ProductAttributeBridge implements \Oracle\M2\Core\Catalog\ProductAttributeCacheInterface
{
    protected $_attributeFactory;
    protected $_attributes;
    protected $_searchBuilder;

    /**
     * @param \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $attributeFactory
     */
    public function __construct(
        \Magento\Framework\Api\SearchCriteriaBuilder $searchBuilder,
        \Magento\Catalog\Api\ProductAttributeRepositoryInterface $attributeFactory
    ) {
        $this->_searchBuilder = $searchBuilder;
        $this->_attributeFactory = $attributeFactory;
    }

    /**
     * @see parent
     */
    public function getOptionArray()
    {
        if (is_null($this->_attributes)) {
            $this->_attributes = [];
            foreach ($this->getCollection() as $attribute) {
                $this->_attributes [] = [
                    'id' => $attribute->getAttributeCode(),
                    'name' => $attribute->getFrontendLabel()
                ];
            }
        }
        return $this->_attributes;
    }

    /**
     * @see parent
     */
    public function getCollection()
    {
        $this->_searchBuilder->addFilter('frontend_label', '', 'neq');
        return $this->_attributeFactory->getList($this->_searchBuilder->create())->getItems();
    }
}

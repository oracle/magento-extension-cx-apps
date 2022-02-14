<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Rating\Model;

class Manager implements \Oracle\M2\Product\CatalogMapperManagerInterface
{
    protected $_reviewFactory;
    protected $_objects;
    protected $_caches = [];

    /**
     * @param \Magento\Reports\Model\ResourceModel\Review\Product\Collection $reviewsFactory
     * @param \Oracle\M2\Core\DataObjectFactory $objects
     */
    public function __construct(
        \Magento\Reports\Model\ResourceModel\Review\Product\CollectionFactory $reviewsFactory,
        \Oracle\M2\Core\DataObjectFactory $objects
    ) {
        $this->_reviewFactory = $reviewsFactory;
        $this->_objects = $objects;
    }

    /**
     * @see parent
     */
    public function getByProduct($product)
    {
        if (!array_key_exists($product->getId(), $this->_caches)) {
            $collection = $this->_reviewFactory->create()
                ->addFieldToFilter('entity_id', [ 'eq' => $product->getId() ]);
            $this->_caches[$product->getId()] = $this->_objects->create([
                'data' => [
                    'review_cnt' => 0,
                    'avg_rating' => 0,
                    'avg_rating_approved' => 0,
                    'last_created' => null
                ]
            ]);
            foreach ($collection as $entry) {
                $this->_caches[$product->getId()] = $entry;
                break;
            }
        }
        return $this->_caches[$product->getId()];
    }
}

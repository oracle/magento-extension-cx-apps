<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Connector\Model\ResourceModel\Event;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init('Oracle\Connector\Model\Event', 'Oracle\Connector\Model\ResourceModel\Event');
    }

    /**
     * Add an array of ids to filter on
     *
     * @param array $ids
     * @return $this
     */
    public function addIdsToFilter($ids)
    {
        $this->addFieldToFilter('entity_id', [ 'in' => $ids ]);
        return $this;
    }

    /**
     * Orders the results the newly created events
     *
     * @return $this
     */
    public function orderByOldest()
    {
        $this->setOrder('created_at', 'asc');
        return $this;
    }
}

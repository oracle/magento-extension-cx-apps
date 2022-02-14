<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Connector\Model\ResourceModel\Registration;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init('Oracle\Connector\Model\Registration', 'Oracle\Connector\Model\ResourceModel\Registration');
    }

    /**
     * @param boolean $active
     * @return $this
     */
    public function addActiveFilter($active = true)
    {
        $this->addFieldToFilter('is_active', ['eq' => $active]);
        return $this;
    }
}

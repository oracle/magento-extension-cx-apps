<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Email\Model\ResourceModel\Trigger;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init('Oracle\Email\Model\Trigger', 'Oracle\Email\Model\ResourceModel\Trigger');
    }

    /**
     * Deletes all of the triggers set to trigger days in the past
     *
     * @param string $siteId
     * @param int $daysInthePast
     * @return void
     */
    public function deleteExpiredItems($siteId, $daysInthePast)
    {
        $adapter = $this->getConnection();
        $newTime = strtotime("-{$daysInthePast} days");
        $newTime = date('Y-m-d H:i:s', $newTime);
        $adapter->delete(
            $this->getMainTable(),
            [
                $adapter->quoteInto('site_id = ?', $siteId),
                $adapter->quoteInto('triggered_at < ?', $newTime)
            ]
        );
    }
}

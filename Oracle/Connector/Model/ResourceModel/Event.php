<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Connector\Model\ResourceModel;

use Oracle\Connector\Setup\InstallSchema;

class Event extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(InstallSchema::EVENT_QUEUE_TABLE, 'entity_id');
    }

    /**
     * @param \Oracle\Connector\Model\Event $model
     * @param string $siteId
     * @param string $eventType
     * @return $this
     */
    public function loadByEventType($model, $siteId, $eventType)
    {
        $read = $this->getConnection();
        $fieldName = $read->quoteIdentifier(sprintf('%s.%s', $this->getMainTable(), 'event_type'));
        $select = $this
            ->_getLoadSelect('site_id', $siteId, $model)
            ->where("{$fieldName} = ?", $eventType);
        $data = $read->fetchRow($select);
        if ($data) {
            $model->setData($data);
        }
        $this->unserializeFields($model);
        $this->_afterLoad($model);
        return $this;
    }
}

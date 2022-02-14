<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Connector\Model\ResourceModel;

use Oracle\Connector\Setup\InstallSchema;

class Registration extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(InstallSchema::REGISTRATION_TABLE, 'entity_id');
    }

    /**
     * @param \Oracle\Connector\Model\Registration $model
     * @param string $scopeType
     * @param int $scopeId
     * @return $this
     */
    public function loadByScope($model, $scopeType, $scopeId)
    {
        $read = $this->getConnection();
        $field = $read->quoteIdentifier(sprintf('%s.%s', $this->getMainTable(), 'scope_id'));
        $select = $this
            ->_getLoadSelect('scope', $scopeType, $model)
            ->where($field . '=?', $scopeId);
        $data = $read->fetchRow($select);
        if ($data) {
            $model->setData($data);
        }
        $this->unserializeFields($model);
        $this->_afterLoad($model);
        return $this;
    }
}

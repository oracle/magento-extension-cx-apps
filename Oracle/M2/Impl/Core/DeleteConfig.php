<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Impl\Core;

class DeleteConfig extends \Magento\Config\Model\ResourceModel\Config
{
    /**
     * Override for deleting everything
     *
     * @param string $path
     * @param string $scopeName
     * @param string $scopeId
     */
    public function deleteAll($path, $scopeName, $scopeId)
    {
        $adapter = $this->getConnection();
        $adapter->delete(
            $this->getMainTable(),
            [
                $adapter->quoteInto('path LIKE ?', $path . '%'),
                $adapter->quoteInto('scope = ?', $scopeName),
                $adapter->quoteInto('scope_id = ?', $scopeId)
            ]
        );
    }
}

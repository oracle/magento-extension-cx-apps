<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Connector\Model\ResourceModel;

use Oracle\Connector\Model\Spi\TidResourceInterface;
use Oracle\Connector\Setup\InstallSchema;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Class Tid
 * @package Oracle\Connector\Model\ResourceModel
 */
class Tid extends AbstractDb implements TidResourceInterface
{
    /**
     * Tid Constructor.
     */
    protected function _construct()
    {
        $this->_init(InstallSchema::TID_TABLE, 'id');
    }
}
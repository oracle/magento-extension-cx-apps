<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Connector\Controller\Adminhtml\Connector;

class Trigger extends \Oracle\Connector\Controller\Adminhtml\Connector
{
    /**
     * @see parent
     */
    protected function _execute($registration)
    {
        if ($this->_middleware->triggerFlush($registration)) {
            return [ 'message' => 'success' ];
        } else {
            return [ 'message' => 'failed', 'code' => 400 ];
        }
    }
}

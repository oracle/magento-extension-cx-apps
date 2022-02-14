<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Connector\Controller\Adminhtml\Connector;

class Health extends \Oracle\Connector\Controller\Adminhtml\Connector
{
    /**
     * @see parent
     */
    protected function _execute($registration)
    {
        return ["live" => "true"];
    }
}

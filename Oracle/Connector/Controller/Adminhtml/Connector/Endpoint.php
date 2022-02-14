<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Connector\Controller\Adminhtml\Connector;

class Endpoint extends \Oracle\Connector\Controller\Adminhtml\Connector
{
    /**
     * @see parent
     */
    protected function _execute($registration)
    {
        $serviceName = $this->getRequest()->getParam('service');

        if ($serviceName) {
            $endpoint = $this->_connector->endpoint($registration, $serviceName);
            if (empty($endpoint)) {
                return ['message' => "{$serviceName} not found.", 'code' => 404];
            } else {
                return $endpoint;
            }
        } else {
            return ['message' => 'No service provided.', 'code' => 400];
        }
    }
}

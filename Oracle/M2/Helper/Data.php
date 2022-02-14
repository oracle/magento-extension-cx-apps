<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const CONNECTOR_CONTROLLER_NAME = 'connector';

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;

    /**
     * @param \Magento\Framework\App\Request\Http $request
     */
    public function __construct(\Magento\Framework\App\Request\Http $request)
    {
        $this->request = $request;
    }

    /**
     * Was the request initiated by the Connector
     *
     * @return bool
     */
    public function invokedByConnector()
    {
        $controllerName = trim($this->request->getControllerName());
        return ($controllerName == self::CONNECTOR_CONTROLLER_NAME);
    }
    
    /**
     * @return string|null
     */
    public function getFireInstanceId()
    {
        $match = [];
        if (preg_match("/fireInstanceId=(\d+)\)/", $this->request->getHeader('User-Agent'), $match)) {
            return $match[1];
        }
        return null;
    }

    /**
     * Convenience method for retrieving a formatted string of the Fire Instance ID for adding to debug log messages.
     * @return string
     */
    public function getFireInstanceIdString()
    {
        $fireInstanceId = $this->getFireInstanceId();
        return $fireInstanceId ? "(Fire Instance ID = $fireInstanceId) " : "";
    }
}

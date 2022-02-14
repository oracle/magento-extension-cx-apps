<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Connector\Controller\Adminhtml\Connector;

class Script extends \Oracle\Connector\Controller\Adminhtml\Connector
{
    /**
     * @see parent
     */
    protected function _execute($registration)
    {
        $extensionId = $this->getRequest()->getParam('extensionId');
        $scriptId = $this->getRequest()->getParam('scriptId');
        if (empty($extensionId) || empty($scriptId)) {
            return [
                'message' => __('Required: extensionId and scriptId'),
                'code' => 400
            ];
        } else {
            $content = $this->getRequest()->getContent();
            $data = [];
            if (!empty($content)) {
                $data = $this->_encoder->decode($content);
            }
            $this->_logger->info(
                "Executing Oracle script {$extensionId}_{$scriptId}. " . $this->getMemoryUsageString()
            );
            /* @param $executionResults [] */
            // we are executing script here
            $executionResults = $this->_connector->executeScript($registration, [
                'extensionId' => $extensionId,
                'id' => $scriptId,
                'data' => $data
            ]);
            $this->_logger->info(
                "Completed Oracle script {$extensionId}_{$scriptId} execution. " . $this->getMemoryUsageString()
            );
            return $executionResults;
        }
    }

    /**
     * Gets the current and peak memory usage, in gigabytes, for the PHP process
     *
     * @return string
     */
    private function getMemoryUsageString()
    {
        return "Current memory usage: " . memory_get_usage() / 1000000000 . " Gb. "
            . "Peak usage: " . memory_get_peak_usage() / 1000000000 . " Gb";
    }
}

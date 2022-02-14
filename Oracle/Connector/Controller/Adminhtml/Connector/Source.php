<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Connector\Controller\Adminhtml\Connector;

class Source extends \Oracle\Connector\Controller\Adminhtml\Connector
{
    /**
     * @see parent
     */
    protected function _execute($registration)
    {
        $sourceId = $this->getRequest()->getParam('object');
        if ($sourceId) {
            $content = $this->getRequest()->getContent();
            $data = [];
            if (!empty($content)) {
                $data = $this->_encoder->decode($content);
            }
            return $this->_connector->source($registration, $sourceId, $data);
        } else {
            return ['message' => __('No Source provided.'), 'code' => 400];
        }
    }
}

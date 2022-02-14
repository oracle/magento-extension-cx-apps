<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Connector\Block\Adminhtml\Registration\Grid\Renderer;

class Action extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Action
{
    /**
     * @see parent
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $actions = [];

        $actions[] = [
            'url' => $this->getUrl('*/*/delete', ['id' => $row->getId()]),
            'caption' => __('Delete'),
        ];

        if (!$row->getIsActive()) {
            $actions[] = [
               'url' => $this->getUrl('*/*/register', ['id' => $row->getId()]),
               'caption' => __('Register')
            ];
        }
        $this->getColumn()->setActions($actions);
        return parent::render($row);
    }
}

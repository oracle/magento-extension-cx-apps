<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Connector\Block\Adminhtml;

class Registration extends \Magento\Backend\Block\Template
{
    protected $_template = 'registration/list.phtml';

    /**
     * @return $this
     */
    protected function _prepareLayout()
    {
        $this->getToolbar()->addChild(
            'add_button',
            'Magento\Backend\Block\Widget\Button',
            [
                'label' => __('Add Registration'),
                'onclick' => "window.location='{$this->getCreateUrl()}'",
                'class' => 'add primary add-registration'
            ]
        );
        return parent::_prepareLayout();
    }

    /**
     * @return string
     */
    public function getCreateUrl()
    {
        return $this->getUrl('*/*/new');
    }

    /**
     * Get Header Text
     *
     * @return string
     */
    public function getHeaderText()
    {
        return __('Responsys Connector Registrations');
    }
}

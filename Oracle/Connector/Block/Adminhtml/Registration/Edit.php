<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Connector\Block\Adminhtml\Registration;

class Edit extends \Magento\Backend\Block\Widget\Form\Container
{
    protected $_coreRegistry;

    /**
     * @param \Magento\Backend\Block\Widget\Context $context,
     * @param \Magento\Framework\Registry $registry,
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_objectId = 'id';
        $this->_blockGroup = 'Oracle_Connector';
        $this->_controller = 'adminhtml_registration';
        parent::_construct();
    }

    /**
     * @return string
     */
    public function getHeaderText()
    {
        $registration = $this->_coreRegistry->registry('current_registration');
        if ($registration->getId()) {
            return __("Edit Rule '%1'", $this->escapeHtml($registration->getName()));
        } else {
            return __('New Registration');
        }
    }
}

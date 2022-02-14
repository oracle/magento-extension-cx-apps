<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Connector\Controller\Adminhtml\Registration;

class Edit extends \Oracle\Connector\Controller\Adminhtml\Registration
{
    protected $_coreRegistry;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Oracle\Connector\Model\MiddlewareInterface $middleware
     * @param \Magento\Framework\Registry $coreRegistry
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Oracle\M2\Connector\MiddlewareInterface $middleware,
        \Magento\Framework\Registry $coreRegistry
    ) {
        parent::__construct($context, $middleware);
        $this->_coreRegistry = $coreRegistry;
    }

    /**
     * Edit Oracle Registration
     *
     * @return void
     */
    public function execute()
    {
        $registration = $this->_registration();
        $this->_coreRegistry->register('current_registration', $registration);

        $this->_view->loadLayout();
        $this->_setActiveMenu('Oracle_Connector::registration');
        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Responsys Connector'));
        if ($registration->getId()) {
            $this->_view->getPage()->getConfig()->getTitle()->prepend(__($registration->getName()));
            $this->_addBreadcrumb(
                __('Edit Registration'),
                __('Edit ' . $registration->getName())
            );
        } else {
            $this->_view->getPage()->getConfig()->getTitle()->prepend(__('New Registration'));
            $this->_addBreadcrumb(
                __('Create Oracle Registration'),
                __('New Registration')
            );
        }
        $values = $this->_getSession()->getData('oracleconnector_registration_form_data', true);
        if ($values) {
            $registration->addData($values);
        }
        $this->_view->renderLayout();
    }
}

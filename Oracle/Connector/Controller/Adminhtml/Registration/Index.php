<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Connector\Controller\Adminhtml\Registration;

class Index extends \Oracle\Connector\Controller\Adminhtml\Registration
{
    /**
     * @see parent
     * @return void
     */
    public function execute()
    {
        if ($this->getRequest()->getQuery('ajax')) {
            $this->_forward('grid');
        } else {
            $this->_view->loadLayout();
            $this->_setActiveMenu('Oracle_Connector::registration');
            $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Responsys Connector'));
            $this->_addBreadcrumb(
                __('Oracle Connector Registrations'),
                __('Oracle Connector Registrations')
            );
            $this->_view->renderLayout();
        }
    }
}

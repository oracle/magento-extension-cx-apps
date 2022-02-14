<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Connector\Controller\Adminhtml;

abstract class Registration extends \Magento\Backend\App\Action
{
    protected $_middleware;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Oracle\M2\Connector\MiddlewareInterface $middleware
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Oracle\M2\Connector\MiddlewareInterface $middleware
    ) {
        parent::__construct($context);
        $this->_middleware = $middleware;
    }

    /**
     * Creates or loads a registration object
     *
     * @return \Oracle\Connector\Model\Registration
     */
    protected function _registration()
    {
        $registration = $this->_objectManager->create('Oracle\Connector\Model\Registration');
        $regId = (int)$this->getRequest()->getParam('id');
        if ($regId) {
            $registration->load($regId);
        }
        return $registration;
    }

    /**
     * @see parent
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Oracle_Connector::registration');
    }
}

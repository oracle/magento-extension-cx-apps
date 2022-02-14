<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Connector\Controller\Adminhtml\Registration;

class Save extends \Oracle\Connector\Controller\Adminhtml\Registration
{
    protected $_storeManager;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Oracle\Connector\Model\MiddlewareInterface $middleware
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Oracle\M2\Connector\MiddlewareInterface $middleware,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        parent::__construct($context, $middleware);
        $this->_storeManager = $storeManager;
    }

    /**
     * Persists the registration form in the DB
     *
     * @return void
     */
    public function execute()
    {
        if (!$this->getRequest()->isPost()) {
            $this->_forward('index');
        } else {
            $registration = $this->_objectManager->create('Oracle\Connector\Model\Registration');
            $id = (int)$this->getRequest()->getParam('entity_id');
            if ($id) {
                $registration->load($id);
            }

            try {
                if (!$this->getRequest()->getParam('scopeHash')) {
                    throw new \InvalidArgumentException("Missing scope.");
                }

                if ($registration->getId()) {
                    $status = $this->_middleware->deregister($registration);
                    if (!$status) {
                        throw new \RuntimeException("Failed to cleanup existing registration");
                    }
                    elseif ($status == -1) {
                        throw new \RuntimeException("{$registration->getName()}: This connection cannot be deleted due to existing dependencies. To view a list of these dependencies, please refer to the app configuration page.");
                    }
                }

                $registration->addData(
                    $this->getRequest()->getParams()
                )->setName(
                    $this->getRequest()->getParam('name')
                )->setUserName(
                    $this->getRequest()->getParam('username')
                )->setPassword(
                    $this->getRequest()->getParam('password')
                )->setIsProtected(
                    (boolean) $this->getRequest()->getParam('is_protected')
                )->setEnvironment(
                    $this->getRequest()->getParam('environment')
                )->setConnectorKey(
                    $this->getRequest()->getParam('connector_key')
                )->setScopeHash(
                    $this->getRequest()->getParam('scopeHash')
                )->setUpdatedAt(
                    $this->_objectManager->get('Magento\Framework\Stdlib\DateTime\DateTime')->gmtDate()
                );

                switch ($registration->getScope()) {
                    case 'default':
                        $registration->setName(__('Default'));
                        break;
                    case 'website':
                        $registration->setName($this->_storeManager
                            ->getWebsite($registration->getScopeId())
                            ->getName());
                        break;
                    default:
                        $registration->setName($this->_storeManager
                            ->getStore($registration->getScopeId())
                            ->getName());
                }

                $registration->save();
                // Attempt to register, or re-register
                if (!$this->_middleware->register($registration)) {
                    $registration->delete();
                    throw new \RuntimeException(__('Failed to register %1', $registration->getName()));
                } else {
                    $registration->setIsActive(true)->save();
                }

                $this->messageManager->addSuccess(__('The registration has been saved.'));
                $this->_getSession()->setFormData(false);
                $this->_redirect('*/*/index');
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError(nl2br($e->getMessage()));
                $this->_getSession()->setData('oracleconnector_registration_form_data', $this->getRequest()->getParams());
                $this->_forward('new');
            } catch (\InvalidArgumentException $e) {
                $this->messageManager->addException($e, __('Highest Root Scope is already registered.'));
                $this->_getSession()->setData('oracleconnector_registration_form_data', $this->getRequest()->getParams());
                $this->_forward('new');
            } catch (\Exception $e) {
                $this->messageManager->addException($e, __('Something went wrong while saving this registration.'));
                $this->_getSession()->setData('oracleconnector_registration_form_data', $this->getRequest()->getParams());
                $this->_forward('new');
            }
        }
    }
}

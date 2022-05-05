<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Connector\Block\Adminhtml\Registration\Edit;

class Form extends \Magento\Backend\Block\Widget\Form\Generic
{
    protected $_dependenceFactory;

    /**
     * @param \Magento\Backend\Block\Widget\Form\Element\DependenceFactory $dependenceFactory
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param array $data = []
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Form\Element\DependenceFactory $dependenceFactory,
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $data);
        $this->_dependenceFactory = $dependenceFactory;
    }

    /**
     * @see parent
     */
    protected function _prepareForm()
    {
        $registration = $this->_coreRegistry->registry('current_registration');

        $form = $this->_formFactory->create([
            'data' => [
                'id' => 'edit_form',
                'action' => $this->getUrl('*/*/save'),
                'method' => 'post'
            ]
        ]);

        $fieldset = $form->addFieldset('base_fieldset', [
            'legend' => __('Registration Information')
        ]);
        $fieldset->addType('scope', '\Oracle\Connector\Block\Adminhtml\Registration\Edit\Form\Element\Scope');

        if ($registration->getId()) {
            $fieldset->addField('entity_id', 'hidden', ['name' => 'entity_id']);
        }

        $fieldset->addField('name', 'hidden', [
            'name' => 'name'
        ]);

        $fieldset->addField('environment', 'select', [
            'name' => 'environment',
            'title' => __('Environment'),
            'label' => __('Environment'),
            'options' => [
                'SANDBOX' => __('Sandbox'),
                'PRODUCTION' => __('Production')
            ]
        ]);

        $fieldset->addField('scope', 'scope', [
            'label' => __('Root Scope'),
            'title' => __('Root Scope'),
            'required' => true,
            'scopeHash' => $registration->getScopeHash(true)
        ]);

        $fieldset->addField('connector_key', 'text', [
            'label' => __('Account ID'),
            'title' => __('Account ID'),
            'required' => true,
            'name' => 'connector_key'
        ]);

        $protected = $fieldset->addField('is_protected', 'select', [
            'label' => __('Basic Auth Protected'),
            'title' => __('Basic Auth Protected'),
            'required' => true,
            'name' => 'is_protected',
            'options' => [
                0 => __('No'),
                1 => __('Yes')
            ],
            'note' => __('Oracle Connector will required network communication to the admin store. If your admin store is protected by Basic Auth, then select <em>Yes</em>, and fill in the username and password below. If your admin store is protected by a firewall, then you must allow network communication for incoming and outgoing requests to <strong>apps.p02.eloqua.com</strong>. Your credentials are only used for Oracle Connector communication on encrypted channels.')
        ]);

        $username = $fieldset->addField('username', 'text', [
            'label' => __('Username'),
            'title' => __('Username'),
            'name' => 'username'
        ]);

        $password = $fieldset->addField('password', 'password', [
            'label' => __('Password'),
            'title' => __('Password'),
            'name' => 'password'
        ]);

        $dependence = $this->_dependenceFactory->create();
        $dependence
            ->addFieldMap($protected->getHtmlId(), $protected->getName())
            ->addFieldMap($username->getHtmlId(), $username->getName())
            ->addFieldMap($password->getHtmlId(), $password->getName())
            ->addFieldDependence(
                $username->getName(),
                $protected->getName(),
                '1'
            )
            ->addFieldDependence(
                $password->getName(),
                $protected->getName(),
                '1'
            );
        $protected->setAfterElementHtml($dependence->toHtml());
        $form->setValues($registration->getData());
        $form->setUseContainer(true);
        $this->setForm($form);
        $this->_eventManager->dispatch('adminhtml_system_oracleconnector_edit_prepare_form', ['form' => $form]);

        return parent::_prepareForm();
    }
}

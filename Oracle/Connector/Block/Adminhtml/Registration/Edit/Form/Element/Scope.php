<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Connector\Block\Adminhtml\Registration\Edit\Form\Element;

class Scope extends \Magento\Framework\Data\Form\Element\AbstractElement
{
    protected $_systemStore;
    protected $_objectFactory;
    protected $_registrations;
    protected $_scopeTrees;
    protected $_middleware;
    protected $_formData;
    protected $_previous = null;

    /**
     * @param \Magento\Framework\Data\Form\Element\Factory $factory
     * @param \Magento\Framework\Data\Form\Element\CollectionFactory $collection
     * @param \Magento\Framework\Data\Form\Element\Escaper $escaper
     * @param \Magento\Store\Model\System\Store $systemStore
     * @param \Oracle\Connector\Model\ResourceModel\Registration\CollectionFactory $registrations
     * @param \Oracle\Connector\Model\MiddlewareInterface $middleware
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Data\Form\Element\Factory $factory,
        \Magento\Framework\Data\Form\Element\CollectionFactory $collection,
        \Magento\Framework\Escaper $escaper,
        \Magento\Framework\DataObjectFactory $objectFactory,
        \Magento\Store\Model\System\Store $systemStore,
        \Oracle\Connector\Model\ResourceModel\Registration\CollectionFactory $registrations,
        \Oracle\M2\Connector\MiddlewareInterface $middleware,
        array $data = []
    ) {
        $this->_systemStore = $systemStore;
        $this->_objectFactory = $objectFactory;
        $this->_registrations = $registrations;
        $this->_middleware = $middleware;
        $this->_formData = $data;
        $this->_scopeTrees = [];
        parent::__construct($factory, $collection, $escaper, $data);
    }

    /**
     * @see parent
     */
    public function getElementHtml()
    {
        return $this->_printCheckboxTree() . $this->getAfterElementHtml();
    }

    /**
     * Moving info out of constructor
     *
     * @return array
     */
    protected function _scopeTrees()
    {
        if (empty($this->_scopeTrees)) {
            if (array_key_exists('scopeHash', $this->_formData)) {
                $this->_previous = $this->_formData['scopeHash'];
            }
            $active = $this->_registrations->create()->addActiveFilter();
            foreach ($active as $registration) {
                if ($registration->getScopeHash(true) === $this->_previous) {
                    continue;
                }
                $this->_scopeTrees[] = [
                    $registration,
                    $this->_middleware->scopeTree($registration)
                ];
            }
        }
        return $this->_scopeTrees;
    }

    /**
     * Prints the radio of selectable scope
     *
     * @param string $type
     * @param mixed $node
     * @return string
     */
    protected function _printCheckbox($type, $node)
    {
        $scopeHash = "{$type}.{$node->getId()}";
        $scopeId = "{$scopeHash}.{$node->getCode()}";
        foreach ($this->_scopeTrees() as list($registration, $scopeTree)) {
            $holderName = "{$registration->getId()}:{$registration->getName()}";
            switch ($registration->getScope()) {
                case 'default':
                    return "<label>{$node->getName()} [$holderName]</label>";
                case 'store':
                    if ($type == 'website') {
                        foreach ($node->getStores() as $store) {
                            if ($store->getId() == $registration->getScopeId()) {
                                return "<label>{$node->getName()} [NA]</label>";
                            }
                        }
                    }
                case 'website':
                    if ($type == 'default') {
                        return "<label>{$node->getName()} [NA]</label>";
                    } elseif ($type == 'store' && $registration->getScope() == 'website' && $registration->getScopeId() == $node->getWebsiteId()) {
                        return "<label>{$node->getName()} [$holderName]</label>";
                    }
                default:
                    if ($scopeHash == $scopeTree['id']) {
                        return "<label>{$node->getName()} [$holderName]</label>";
                    }
            }
        }
        $selected = $scopeId === $this->_previous ? 'CHECKED' : '';
        return "<label><input name='scopeHash' value='{$scopeId}' {$selected} type='radio'> {$node->getName()}</label>";
    }

    /**
     * Prints the tree of radios of selectable website and store views
     *
     * @return string
     */
    protected function _printCheckboxTree()
    {
        $default = $this->_objectFactory->create();
        $default->addData([
            'name' => 'Default',
            'code' => 'default',
            'id' => 0
        ]);
        $html = '<ul style="list-style-type:none;margin-top:8px"><li>';
        $html .= $this->_printCheckbox('default', $default);
        foreach ($this->_systemStore->getWebsiteCollection() as $website) {
            $html .= '<ul style="list-style-type:none;margin-left:20px"><li>';
            $html .= $this->_printCheckbox('website', $website);
            $html .= '<ul style="list-style-type:none;margin-left:20px">';
            foreach ($website->getStores() as $store) {
                $html .= '<li>';
                $html .= $this->_printCheckbox('store', $store);
                $html .= '</li>';
            }
            $html .= '</ul></li></ul>';
        }
        return $html . '</li></ul>';
    }
}

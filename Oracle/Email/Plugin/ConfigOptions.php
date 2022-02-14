<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Email\Plugin;

class ConfigOptions
{
    protected $_scopeConfig;
    protected $_helper;
    protected $_request;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Oracle\M2\Email\SettingsInterface $helper
     * @param \Magento\Framework\App\RequestInterface $request
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Oracle\M2\Email\SettingsInterface $helper,
        \Magento\Framework\App\RequestInterface $request
    ) {
        $this->_scopeConfig = $scopeConfig;
        $this->_helper = $helper;
        $this->_request = $request;
    }

    /**
     * Intercepts the dropdown elements to create custom mapping
     *
     * @param mixed $subject
     * @param callable $options
     * @return array
     */
    public function aroundToOptionArray($subject, $options)
    {
        $configPath = $subject->getPath();
        $scopeType = 'default';
        $scopeId = null;
        if ($this->_request->has('website')) {
            $scopeType = 'website';
            $scopeId = $this->_request->getParam('website');
        } elseif ($this->_request->has('store')) {
            $scopeType = 'store';
            $scopeId = $this->_request->getParam('store');
        }
        $templateId = $this->_scopeConfig->getValue($configPath, $scopeType, $scopeId);
        if (empty($templateId)) {
            $templateId = str_replace('/', '_', $configPath);
        }
        $mappingId = $this->_helper->getLookup($templateId, $scopeType, $scopeId, true);
        if ($mappingId) {
            $label = __('-- Configured within Oracle Connector --');
            return [['value' => $templateId, 'label' => $label ]];
        } else {
            return $options();
        }
    }
}

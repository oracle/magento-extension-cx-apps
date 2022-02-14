<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Browse\Block;

class Browse extends \Magento\Framework\View\Element\Template
{
    protected $_settings;
    protected $_coreRegistry;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Oracle\M2\Browse\SettingsInterface $settings
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Oracle\M2\Browse\SettingsInterface $settings,
        \Magento\Framework\Registry $coreRegistry,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_settings = $settings;
        $this->_coreRegistry = $coreRegistry;
    }

    /**
     * Determines if the block is enabled for this frontend
     *
     * @return boolean
     */
    public function isEnabled()
    {
        $currentStore = $this->_storeManager->getStore(true);
        $enabled = $this->_settings->isEnabled('store', $currentStore);
        if ($this->getEventType() == 'SEARCH') {
            return $enabled && $this->_settings->isSearchEnabled('store', $currentStore);
        }
        return $enabled;
    }

    /**
     * Gets the server side browse controller
     *
     * @return string
     */
    public function getBrowseUrl()
    {
        $currentStore = $this->_storeManager->getStore(true);
        $params = [
            '_secure' => $currentStore->isCurrentlySecure(),
            '_escape' => true
        ];
        if ($this->hasEventType()) {
            $params['event_type'] = $this->getEventType();
        }
        $product = $this->_coreRegistry->registry('product');
        if ($product) {
            $params['id'] = $product->getId();
            $params['category_id'] = $product->getCategoryId();
        }
        return $this->_urlBuilder->addQueryParams(
            $this->getRequest()->getQueryValue()
        )->getUrl('bbrowse/browse/capture', $params);
    }
}

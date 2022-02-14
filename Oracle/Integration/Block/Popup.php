<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Integration\Block;

class Popup extends \Magento\Framework\View\Element\Template
{
    protected $_helper;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Oracle\M2\Integration\PopupSettingsInterface $helper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Oracle\M2\Integration\PopupSettingsInterface $helper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_helper = $helper;
    }

    /**
     * Gets the underlying helper for this block
     *
     * @return \Oracle\Integration\Helper\Data
     */
    public function isPopupEnabled()
    {
        return $this->_helper->isPopupEnabled('store', $this->_storeManager->getStore(true));
    }

    /**
     * Gets the underlying helper for this block
     *
     * @return \Oracle\Integration\Helper\Data
     */
    public function getPopupDomains()
    {
        return $this->_helper->getPopupDomains('store', $this->_storeManager->getStore(true));
    }

    /**
     * Gets the popup subscription url
     *
     * @return string
     */
    public function getPopupUrl()
    {
        $currentStore = $this->_storeManager->getStore(true);
        return $this->getUrl('bint/integration/popup', [
            '_secure' => $currentStore->isCurrentlySecure()
        ]);
    }
}

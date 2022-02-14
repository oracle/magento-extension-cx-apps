<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Order\Block;

use Oracle\M2\Connector\SettingsInterface;
use Magento\Customer\CustomerData\SectionSourceInterface;

class Bta extends \Magento\Framework\View\Element\Template implements SectionSourceInterface
{
    /** @var SettingsInterface  */
    private $_helper;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param SettingsInterface $helper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        SettingsInterface $helper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_helper = $helper;
    }

    /**
     * Forwards the siteId for the store to the template
     *
     * @return string
     */
    public function getSiteId()
    {
        return $this->_helper->getTidHash('store', $this->_storeManager->getStore(true));
    }

    /**
     * View-Model (Block) data for KnockoutJS AJAX call
     *
     * @return array
     */
    public function getSectionData()
    {
        return ['siteId' => $this->escapeHtml($this->getSiteId())];
    }
}

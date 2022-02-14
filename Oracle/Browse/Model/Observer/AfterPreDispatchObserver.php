<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Browse\Model\Observer;

class AfterPreDispatchObserver implements \Magento\Framework\Event\ObserverInterface
{
    protected $_settings;

    /**
     * @param \Oracle\M2\Browse\SettingsInterface $settings
     */
    public function __construct(
        \Oracle\M2\Browse\SettingsInterface $settings
    ) {
        $this->_settings = $settings;
    }

    /**
     * @see parent
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $this->_settings->getUniqueCustomerId();
    }
}

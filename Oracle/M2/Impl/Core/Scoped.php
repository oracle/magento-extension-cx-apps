<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Impl\Core;

class Scoped implements \Oracle\M2\Core\Config\ScopedInterface
{
    private $_config;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $config
    ) {
        $this->_config = $config;
    }

    /**
     * @see parent
     */
    public function getValue($path, $scopeName = 'default', $scopeId = null)
    {
        return $this->_config->getValue($path, $scopeName, $scopeId);
    }

    /**
     * @see parent
     */
    public function isSetFlag($path, $scopeName = 'default', $scopeId = null)
    {
        return $this->_config->isSetFlag($path, $scopeName, $scopeId);
    }
}

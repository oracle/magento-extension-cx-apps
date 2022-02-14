<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Impl\Core;

class Currency implements \Oracle\M2\Core\Directory\CurrencyManagerInterface
{
    protected $_currencyFactory;
    protected $_currencyCache = [];

    /**
     * @param \Magento\Directory\Model\CurrencyFactory $currencyFactory
     */
    public function __construct(
        \Magento\Directory\Model\CurrencyFactory $currencyFactory
    ) {
        $this->_currencyFactory = $currencyFactory;
    }

    /**
     * @see \Oracle\M2\Core\Directory\CurrencyManagerInterface::getByCode
     */
    public function getByCode($code)
    {
        if (!array_key_exists($code, $this->_currencyCache)) {
            $this->_currencyCache[$code] = $this->_currencyFactory->create()->load($code);
        }
        return $this->_currencyCache[$code];
    }
}

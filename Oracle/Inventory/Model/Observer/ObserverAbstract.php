<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Inventory\Model\Observer;

abstract class ObserverAbstract implements \Magento\Framework\Event\ObserverInterface
{
    protected $_observer;

    /**
     * @param \Oracle\M2\Inventory\CatalogMapper $observer
     */
    public function __construct(
        \Oracle\M2\Inventory\CatalogMapper $observer
    ) {
        $this->_observer = $observer;
    }
}

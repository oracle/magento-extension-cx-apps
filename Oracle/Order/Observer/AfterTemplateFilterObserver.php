<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Order\Observer;

use Magento\Framework\Event\ObserverInterface;

class AfterTemplateFilterObserver extends ObserverAbstract
{
    /**
     * @see ObserverInterface::execute
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $this->_observer->eventFilter($observer);
    }
}

<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Email;

trait FilterEventTrait
{
    /**
     * Adds the event filter
     * 
     * @see FilterEventInterface::eventFilter
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function eventFilter(\Magento\Framework\Event\Observer $observer)
    {
        $observer->getFilter()->addEventFilter($this);
    }
}

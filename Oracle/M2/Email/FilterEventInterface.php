<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Email;

interface FilterEventInterface
{
    /**
     * Adds the event filter
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function eventFilter(\Magento\Framework\Event\Observer $observer);

    /**
     * Implementors will add Oracle tags $name => $content
     *
     * @param array $message
     * @param array $templateVars
     * @param boolean $forceContext
     * @return array
     */
    public function apply(array $message, array $templateVars = [], $forceContext);
}

<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Email\Observer;

class AfterRedirectPathObserver implements \Magento\Framework\Event\ObserverInterface
{
    protected $_redirect;

    /**
     * @param \Oracle\M2\Email\Redirector $redirect
     */
    public function __construct(\Oracle\M2\Email\Redirector $redirect)
    {
        $this->_redirect = $redirect;
    }

    /**
     * @see parent
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $this->_redirect->redirectPath($observer);
    }
}

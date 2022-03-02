<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Bronto\Contact\Observer;

abstract class CustomerObserverAbstract implements \Magento\Framework\Event\ObserverInterface
{
    protected $_observer;
    protected $_request;

    /**
     * @param \Bronto\Contact\Model\Observer
     */
    public function __construct(
        \Bronto\Contact\Model\Observer $observer,
        \Magento\Framework\App\RequestInterface $req
    ) {
        $this->_observer = $observer;
        $this->_request = $req;
    }
}

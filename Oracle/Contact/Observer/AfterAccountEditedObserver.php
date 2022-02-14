<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Contact\Observer;

class AfterAccountEditedObserver extends ObserverAbstract
{
    protected $_session;

    /**
     * @param \Oracle\Contact\Model\Observer
     * @param \Magento\Customer\Model\Session $session
     */
    public function __construct(
        \Oracle\Contact\Model\Observer $observer,
        \Magento\Customer\Model\Session $session
    ) {
        parent::__construct($observer);
        $this->_session = $session;
    }

    /**
     * @see parent
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $this->_observer->updateEmail(
            $this->_session->getCustomerId(),
            $observer->getRequest()->getParam('email')
        );
    }
}

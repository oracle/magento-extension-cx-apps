<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Contact\Observer;

class AfterCustomerSaveObserver extends CustomerObserverAbstract
{
    /**
     * @see parent
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if($this->_request != null && $this->_request->getModuleName() == "customer"){
            if($this->_request->getActionName() == "resetpasswordpost"){
                return;
            }
            if($this->_request->getActionName() == "resetPassword"){
                $observer->setData("resetPassword", true);
            }
            if($this->_request->getActionName() == "forgotpasswordpost"){
                $observer->setData("forgotPassword", true);
            }
        }
        $this->_observer->pushChanges($observer);
    }
}

<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Connector\Controller\Adminhtml\Registration;

class Delete extends \Oracle\Connector\Controller\Adminhtml\Registration
{
    /**
     * Destroy the registration in the DB.
     *
     * @return void
     */
    public function execute()
    {
        $registration = $this->_registration();
        if ($registration->getId()) {
            try {
                $status = $this->_middleware->deregister($registration);
                if ($status == -1) {
                    throw new \RuntimeException("{$registration->getName()}: This connection cannot be deleted due to existing dependencies. To view a list of these dependencies, please refer to the app configuration page.");
                }
                elseif (!$status)
                {
                    throw new \RuntimeException("Something went wrong while deregistering registration.: {$registration->getName()}");
                }
                $registration->delete();
                $this->messageManager->addSuccess(__('The registration has been deleted.'));
            } catch (\Exception $e) {
                $this->messageManager->addException($e);
            }
        } else {
            $this->messageManager->addError(__('The registration could not be found.'));
        }
        $this->_redirect('*/*/');
    }
}

<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Connector\Controller\Adminhtml\Registration;

class Grid extends \Oracle\Connector\Controller\Adminhtml\Registration
{
    /**
     * @see parent
     * @return void
     */
    public function execute()
    {
        $this->_view->loadLayout(false);
        $this->_view->getLayout()
            ->getMessagesBlock()
            ->setMessages($this->messageManager->getMessages(true));
        $this->_view->renderLayout();
    }
}

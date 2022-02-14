<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Notification\Controller\Adminhtml;

abstract class Notification extends \Magento\Backend\App\Action
{
    protected $_service;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Oracle\M2\Core\Notification\InboxInterface $service
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Oracle\M2\Core\Notification\InboxInterface $service
    ) {
        parent::__construct($context);
        $this->_service = $service;
    }

    /**
     * Unwind the param values
     *
     * @param string $message
     * @return mixed
     */
    protected function _decrypt($message)
    {
        return base64_decode(rawurldecode($message));
    }

    /**
     * @see parent
     */
    protected function _isAllowed()
    {
        return true;
    }
}

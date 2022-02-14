<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Notification\Model\Observer;

use Oracle\Notification\Model\VersionInboxFactory;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Class PredispatchAdminActionControllerObserver
 * @package Oracle\Notification\Model\Observer
 */
class PredispatchAdminActionControllerObserver implements ObserverInterface
{
    /** @var  VersionInboxFactory */
    protected $inboxFactory;
    
    /** @var Session */
    protected $backendAuthSession;

    /**
     * @param FeedFactory $feedFactory
     * @param Session $backendAuthSession
     */
    public function __construct(
        VersionInboxFactory $inboxFactory,
        Session $backendAuthSession
    ) {
        $this->inboxFactory = $inboxFactory;
        $this->backendAuthSession = $backendAuthSession;
    }

    /**
     * Predispatch admin action controller
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        if ($this->backendAuthSession->isLoggedIn()) {
            /** @var VersionInbox $inbox */
            $inbox = $this->inboxFactory->create();
            $inbox->checkUpdate();
        }
    }
}

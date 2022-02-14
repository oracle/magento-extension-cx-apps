<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Impl\Core;

class InboxManager implements \Oracle\M2\Core\Notification\InboxInterface
{
    const REDIRECT_PATH = 'oracle/notification/redirect';

    protected $_notifier;
    protected $_service;
    protected $_inbox;
    protected $_logger;
    protected $_url;

    /**
     * @param \Magento\Framework\Notification\NotifierInterface $notifier
     * @param \Magento\AdminNotification\Model\Inbox $inbox
     * @param \Magento\AdminNotification\Model\NotificationService $service
     * @param \Magento\Backend\Model\UrlInterface $url
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Framework\Notification\NotifierInterface $notifier,
        \Magento\AdminNotification\Model\Inbox $inbox,
        \Magento\AdminNotification\Model\NotificationService $service,
        \Magento\Backend\Model\UrlInterface $url,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->_notifier = $notifier;
        $this->_service = $service;
        $this->_inbox = $inbox;
        $this->_url = $url;
        $this->_logger = $logger;
    }

    /**
     * @see parent
     */
    public function addNotice($title, $description, $url)
    {
        try {
            $this->_notifier->addNotice($title, $description, $url, false);
            $latestNotice = $this->_inbox->loadLatestNotice();
            $url = $this->_wrapUrl($latestNotice->getId(), $url);
            $latestNotice->setUrl($url)->save();
        } catch (\Exception $e) {
            $this->_logger->critical($e);
            return false;
        }
        return true;
    }

    /**
     * @see parent
     */
    public function markAsRead($notificationId)
    {
        try {
            $this->_service->markAsRead($notificationId);
        } catch (\Exception $e) {
            $this->_logger->critical($e);
        }
    }

    /**
     * Wraps a notification url with a redirect path to mark as read
     *
     * @param mixed $notificationId
     * @param string $url
     * @return string
     */
    protected function _wrapUrl($notificationId, $url)
    {
        return $this->_url->getUrl(self::REDIRECT_PATH, [
            'id' => $this->_encode($notificationId),
            'url' => $this->_encode($url)
        ]);
    }

    /**
     * Encodes an encrypted parameter back to itself
     *
     * @param string $message
     * @return string
     */
    protected function _encode($message)
    {
        return rawurlencode(base64_encode($message));
    }
}

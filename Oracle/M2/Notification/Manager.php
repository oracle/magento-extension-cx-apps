<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Notification;

class Manager implements ManagerInterface
{
    const TYPE_GENERAL = 'general';
    const TYPE_RELEASE = 'release';

    protected $_inbox;

    /**
     * @param \Oracle\M2\Core\Notification\InboxInterface $inbox
     */
    public function __construct(
        \Oracle\M2\Core\Notification\InboxInterface $inbox
    ) {
        $this->_inbox = $inbox;
    }

    /**
     * @see parent
     */
    public function createAnnouncements($items)
    {
        $results = [];
        foreach ($items as $item) {
            if ($this->_createNotification($item)) {
                $results[] = $item['id'];
            }
        }
        return $results;
    }

    /**
     * @see parent
     */
    public function markAsRead($notificationId)
    {
        $this->_inbox->markAsRead($notificationId);
    }

    /**
     * Determines if this alert should be written in the platform
     *
     * @param array $item
     * @return boolean
     */
    protected function _isAlert($item)
    {
        return (
            $item['type'] != self::TYPE_GENERAL ||
            preg_match('/^\[?ALERT\]?/i', $item['title'])
        );
    }

    /**
     * Writes this notification as an admin notice
     *
     * @param array $item
     * @return boolean
     */
    protected function _createNotification($item)
    {
        if ($this->_isAlert($item)) {
            $title = "[Oracle Notification] {$item['title']}";
            return $this->_inbox->addNotice($title, $item['description'], $item['url']);
        }
        return true;
    }
}

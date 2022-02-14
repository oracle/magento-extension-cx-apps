<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Notification;

interface ManagerInterface
{
    /**
     * Creates announcements within the platform
     *
     * @param array $items
     * @return array
     */
    public function createAnnouncements($items);

    /**
     * Marks item as read
     *
     * @param mixed $notificationId
     * @return void
     */
    public function markAsRead($notificationId);
}

<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Notification;

interface SettingsInterface extends \Oracle\M2\Connector\Event\HelperInterface
{
    const XML_PATH_ENABLED = 'oracle/advanced/extensions/settings/notification_enabled';
    const XML_PATH_EMAIL = 'oracle/advanced/extensions/settings/notification_email';

    public function getNotificationEmail($scope = 'default', $scopeId = null);
}

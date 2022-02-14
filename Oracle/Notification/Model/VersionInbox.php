<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Notification\Model;

use Magento\AdminNotification\Model\InboxFactory;
use Magento\Framework\Notification\MessageInterface;

/**
 * Class VersionInbox
 * @package Oracle\Notification\Model
 */
class VersionInbox
{
    const DEFAULT_MSG_URL = 'https://helpdocs.oracle.com/bmp/#task/t_bmp_connector_magento_m2_configure.html';

    /** @var VersionFeed */
    private $versionFeed;

    /** @var InboxFactory */
    private $inboxfactory;

    /**
     * VersionInbox constructor.
     * @param VersionFeed $versionFeed
     * @param InboxFactory $inboxFactory
     */
    public function __construct(
        VersionFeed $versionFeed,
        InboxFactory $inboxFactory
    ){
        $this->versionFeed = $versionFeed;
        $this->inboxfactory = $inboxFactory;
    }

    /**
     * Checks for new versions. If so, add new inbox message(s)
     */
    public function checkUpdate()
    {
        if ($this->versionFeed->hasCheckedRecently()) {
            return;
        }
        if (!$this->versionFeed->hasNewVersion()){
            return;
        }
        $feedData = [];
        foreach ($this->versionFeed->getAllReleases() as $version => $release) {
            $publicationDate = strtotime((string)$release->pubDate);
            $description = (isset($release->notes)) ?
                'Release Notes:</br><ul><li>' . implode('</li><li>', $release->notes) . '</li></ul>'
                : '';
            $feedData[] = [
                'severity' => (int) (isset($release->severity) ? $release->severity : MessageInterface::SEVERITY_NOTICE),
                'date_added' => date('Y-m-d H:i:s', $publicationDate),
                'title' => (string) $release->message,
                'description' => $description,
                'url' => isset($release->url) ? $release->url : self::DEFAULT_MSG_URL,
            ];
        }
        $this->versionFeed->setLastUpdate();
        if ($feedData) {
            $this->inboxfactory->create()->parse($feedData);
        }
    }
}
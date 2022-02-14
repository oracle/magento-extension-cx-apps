<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Notification\Model\System\Message;

use Oracle\Notification\Model\VersionFeed;
use Magento\Framework\Notification\MessageInterface;

/**
 * Class VersionSystemMessage
 * @package Oracle\Notification\Model\System\Message
 */
class VersionSystemMessage implements MessageInterface
{
    const MESSAGE_IDENTITY = 'oracle_version_message';

    /** @var VersionFeed */
    private $versionFeed;

    /**
     * VersionSystemMessage constructor.
     * @param VersionFeed $versionFeed
     */
    public function __construct(VersionFeed $versionFeed)
    {
        $this->versionFeed = $versionFeed;
    }

    /**
     * @see MessageInterface::getIdentity
     * @return string
     */
    public function getIdentity()
    {
        return self::MESSAGE_IDENTITY;
    }

    /**
     * @see MessageInterface::isDisplayed
     * @return bool
     */
    public function isDisplayed()
    {
        return $this->versionFeed->hasNewVersion();
    }

    /**
     * @see MessageInterface::getText
     * @return string
     */
    public function getText()
    {
        return $this->versionFeed->getLatestReleaseInfo()->message;
    }

    /**
     * @see MessageInterface\MessageInterface::getSeverity
     * @return int
     */
    public function getSeverity()
    {
        return $this->versionFeed->getLatestReleaseInfo()->severity;
    }
}
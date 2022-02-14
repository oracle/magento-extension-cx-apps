<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Notification\Model;

use Oracle\M2\Connector\Middleware;
use Oracle\M2\Impl\Core\Meta;
use Oracle\M2\Common\Serialize\Json\Standard as Serializer;
use Oracle\M2\Common\Transfer\Adapter;
use Oracle\M2\Common\Transfer\Response;
use Magento\Framework\Notification\MessageInterface;
use Magento\Framework\Model\Context;
use Magento\Framework\App\CacheInterface;

/**
 * Class VersionFeed
 * @package Oracle\Notification\Model
 */
class VersionFeed extends \Magento\Framework\Model\AbstractModel
{
    const VERSION_CHECK_FREQUENCY = 3600;
    const VERSION_QUERY_KEY = 'version';

    const CACHE_KEY_LASTCHECK = 'oracle_notifications_lastcheck';

    /** @var CacheInterface  */
    private $cacheManager;

    /** @var Adapter */
    private $client;

    /** @var Meta */
    private $meta;

    /** @var \Oracle\M2\Common\Transfer\Response */
    private $releaseInfoResponse;

    /**
     * VersionFeed constructor.
     *
     * @param Context $context
     * @param Adapter $client
     * @param Meta $meta
     */
    public function __construct(Context $context, Adapter $client, Meta $meta)
    {
        $this->cacheManager = $context->getCacheManager();
        $this->client = $client;
        $this->meta = $meta;
        $this->_logger = $context->getLogger();
    }

    /**
     * Gets the highest (lowest integer) level severity in the release info set.
     *
     * @see Magento\Framework\Notification\MessageInterface\MessageInterface::getSeverity
     * @return int
     */
    public function getHighestSeverity()
    {
        $highestSeverity = MessageInterface::SEVERITY_NOTICE;
        $releaseInfo = $this->getReleaseInfo();
        if (!isset($releaseInfo->releases)) {
            return $highestSeverity;
        }
        foreach ($releaseInfo->releases as $info) {
            if (isset($info->severity)) {
                $highestSeverity = ($highestSeverity > $info->severity) ? $info->severity : $highestSeverity;
            }
        }
        return $highestSeverity;
    }

    /**
     * Sends an HTTP request to get the all release info between the currently installed version and the latest available
     */
    private function requestForReleaseInfo()
    {
        try {
            $request = $this->client->createRequest('GET', Middleware::getVersionCheckUrl());
            $request->query(self::VERSION_QUERY_KEY, $this->meta->getExtensionVersion());
            $this->setReleaseInfoResponse($request->respond());
        } catch (\Exception $e) {
            $this->_logger->critical(
                'Could not get Oracle release info:' . PHP_EOL
                . $e->getMessage() . PHP_EOL
                . $e->getTraceAsString()
            );
        }
    }

    /**
     * Gets the associative array representation of the json response body
     *
     * @return array
     */
    public function getReleaseInfo()
    {
        $response = $this->getReleaseInfoResponse();
        return $response ? json_decode($response->body()) : [];
    }

    /**
     * Gets the array of releases newer than the current installed version.
     * They are keyed by the version number
     *
     * @return array
     */
    public function getAllReleases()
    {
        $releaseInfo = $this->getReleaseInfo();
        return isset($releaseInfo->releases) ? $releaseInfo->releases : [];
    }

    /**
     * Gets all of the release information for the latest available version
     *
     * @return array
     */
    public function getLatestReleaseInfo()
    {
        $releaseInfo = $this->getReleaseInfo();
        return isset($releaseInfo->releases->{$this->getLatestReleaseVersion()})
            ? $releaseInfo->releases->{$this->getLatestReleaseVersion()}
            : [];
    }

    /**
     * Gets the latest release version number
     *
     * @return string
     */
    public function getLatestReleaseVersion()
    {
        $releaseInfo = $this->getReleaseInfo();
        return isset($releaseInfo->latestVersion)
            ? $releaseInfo->latestVersion
            : $this->meta->getExtensionVersion();
    }

    /**
     * Gets the allowed version check frequency in minutes
     *
     * @return int
     */
    private function getCheckFrequency()
    {
        return self::VERSION_CHECK_FREQUENCY;
    }

    /**
     * Gets the timestamp of the last version check
     *
     * @return int
     */
    public function getLastUpdate()
    {
        return $this->cacheManager->load(self::CACHE_KEY_LASTCHECK);
    }

    /**
     * Set last update timestamp (now)
     *
     * @return self
     */
    public function setLastUpdate()
    {
        $this->cacheManager->save(time(), self::CACHE_KEY_LASTCHECK);
        return $this;
    }

    /**
     * Checks if a new version exists
     *
     * @return bool
     */
    public function hasNewVersion()
    {
        $hasNewVersion = false;
        $installedVersion = $this->meta->getExtensionVersion();
        $latestVersion = $this->getLatestReleaseVersion();
        return version_compare($installedVersion, $latestVersion) < 0;
    }

    /**
     * Returns true if a version check request was made recently
     *
     * @return bool
     */
    public function hasCheckedRecently()
    {
        return ($this->getCheckFrequency() + $this->getLastUpdate()) > time();
    }

    /**
     * @param Response $response
     * @return self
     */
    private function setReleaseInfoResponse(Response $response)
    {
        $this->releaseInfoResponse = $response;
        return $this;
    }

    /**
     * Gets the Response object of the HTTP request to retrieve release information
     *
     * @return Response
     */
    private function getReleaseInfoResponse()
    {
        if (!$this->releaseInfoResponse) {
            $this->requestForReleaseInfo();
        }
        return $this->releaseInfoResponse;
    }

}

<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Impl\Core;

use Oracle\M2\Core\Cookie\ReaderInterface;
use Oracle\M2\Core\Cookie\WriterInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadata;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;

class Cookies implements ReaderInterface, WriterInterface
{
    /** @var CookieManagerInterface */
    protected $cookieManager;
    
    /** @var CookieMetadataFactory */
    protected $metadataFactory;

    /** @var SessionManagerInterface */
    protected $sessionManager;

    /** @var Logger */
    protected $logger;

    /**
     * @param CookieManagerInterface $cookieManager
     * @param CookieMetadataFactory $metadataFactory
     * @param Logger
     */
    public function __construct(
        CookieManagerInterface $cookieManager,
        CookieMetadataFactory $metadataFactory,
        SessionManagerInterface $sessionManager,
        Logger $logger
    ) {
        $this->cookieManager = $cookieManager;
        $this->metadataFactory = $metadataFactory;
        $this->sessionManager = $sessionManager;
        $this->logger = $logger;
    }

    /**
     * @see ReaderInterface::getCookie
     */
    public function getCookie($name, $defaultValue)
    {
        return $this->cookieManager->getCookie($name, $defaultValue);
    }

    /**
     * @see WriterInterface::setServerCookie
     */
    public function setServerCookie($name, $value, array $metadata = [])
    {
        // Set duration on sensitive cookie
        if (!array_key_exists(CookieMetadata::KEY_DURATION, $metadata)) {
            $metadata[CookieMetadata::KEY_DURATION] = 3600 * 24 * 365;
        }
        if (!array_key_exists(CookieMetadata::KEY_PATH, $metadata)) {
            $metadata[CookieMetadata::KEY_PATH] = '/';
        }
        $cookie = $this->metadataFactory->createSensitiveCookieMetadata($metadata);
        $this->cookieManager->setSensitiveCookie($name, $value, $cookie);
    }

    /**
     * @see WriterInterface::deleteCookie
     *
     * @param string $name
     * @param CookieMetadata $metadata [null]
     */
    public function deleteCookie($name, CookieMetadata $metadata = null)
    {
        try {
            $this->cookieManager->deleteCookie($name, $metadata);
        } catch (\Exception $e) {
            $this->logger->debug("Not able to delete the {$name} cookie: " . $e->getMessage(), $e->getTrace());
        }
    }
}

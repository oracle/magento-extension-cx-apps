<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Cart\Plugin;

use Oracle\Connector\Api\TidRepositoryInterface;
use Oracle\M2\Connector\SettingsInterface;
use Oracle\M2\Core\Cookie\ReaderInterface;
use Oracle\M2\Impl\Core\Cookies;
use Oracle\M2\Impl\Core\Logger;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;
use Magento\Store\Model\StoreManager;

/**
 * Class Get
 * @package Oracle\Cart\Plugin
 */
class Get
{
    /** @var TidRepositoryInterface */
    protected $tidRepo;

    /** @var ExtensionAttributesFactory */
    protected $extensionAttributesFactory;

    /** @var SettingsInterface */
    protected $connectorSettings;

    /** @var StoreManager */
    protected $storeManager;

    /** @var ReaderInterface */
    protected $cookieReader;

    /** @var Logger */
    protected $logger;

    /**
     * Get constructor.
     *
     * @param TidRepositoryInterface $tidRepo
     * @param ExtensionAttributesFactory $extensionAttributesFactory
     * @param SettingsInterface $connectorSettings
     * @param StoreManager $storeManager
     * @param ReaderInterface $cookieReader
     */
    public function __construct(
        TidRepositoryInterface $tidRepo,
        ExtensionAttributesFactory $extensionAttributesFactory,
        SettingsInterface $connectorSettings,
        StoreManager $storeManager,
        ReaderInterface $cookieReader,
        Logger $logger
    ) {
        $this->tidRepo = $tidRepo;
        $this->extensionAttributesFactory = $extensionAttributesFactory;
        $this->connectorSettings = $connectorSettings;
        $this->storeManager = $storeManager;
        $this->cookieReader = $cookieReader;
        $this->logger = $logger;
    }

    /**
     * @param CartRepositoryInterface $cartRepo
     * @param CartInterface $cart [null]
     * @return CartInterface|null
     */
    public function afterGet(
        CartRepositoryInterface $cartRepo,
        CartInterface $cart = null
    ) {
        if (!$cart) {
            return $cart;
        }
        return $this->attachTid($cartRepo, $cart);
    }

    /**
     * @param CartInterface $cart
     * @return CartInterface
     * @throws LocalizedException if there was an issue loading the Tid
     */
    private function attachTid(CartRepositoryInterface $cartRepo, CartInterface $cart)
    {
        $extensionAttributes = $cart->getExtensionAttributes() ?: $this->extensionAttributesFactory->create(Quote::class);
        $tid = $extensionAttributes->getOracleTid();
        if (!$tid) {
            try {
                $tid = $this->tidRepo->getByCartId($cart->getId());
            } catch (\Exception $e) {
                throw new LocalizedException(new Phrase('An exception was thrown while loading the TID'), $e);
            }
        }
        $cookie = $this->getCookie();
        if (!$tid && !empty($cookie)) {
            try {
                $tid = $this->tidRepo->create($cookie, $cart->getId());
            } catch (\DomainException $de) {
                $this->logger->critical('TID cookie contained an invalid value');
            }
        }
        if ($tid) {
            $cart->setExtensionAttributes($extensionAttributes->setOracleTid($tid));
        }
        return $cart;
    }

    /**
     * Gets the TID cookie
     *
     * @return string
     */
    private function getCookie()
    {
        $store = $this->storeManager->getStore('');
        $storeId = $store->getId();
        return $this->cookieReader->getCookie($this->connectorSettings->getTidKey('store', $storeId), null);
    }
}

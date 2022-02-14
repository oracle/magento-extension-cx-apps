<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Cart\Plugin;

use Oracle\Connector\Api\Data\TidInterface;
use Oracle\Connector\Api\TidRepositoryInterface;
use Oracle\M2\Connector\SettingsInterface;
use Oracle\M2\Core\Cookie\ReaderInterface;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Store\Model\StoreManager;

/**
 * Class Save
 * 
 * @package Oracle\Cart\Plugin
 */
class Save
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

    /**
     * Save constructor.
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
        ReaderInterface $cookieReader
    ) {
        $this->tidRepo = $tidRepo;
        $this->extensionAttributesFactory = $extensionAttributesFactory;
        $this->connectorSettings = $connectorSettings;
        $this->storeManager = $storeManager;
        $this->cookieReader = $cookieReader;
    }

    /**
     * Perform tasks after before a cart
     * @param CartRepositoryInterface $cartRepo
     * @param CartInterface|mixed $arguments,...
     * @return []
     */
    public function beforeSave(CartRepositoryInterface $cartRepo, ...$arguments)
    {
        $cart = array_shift($arguments);
        if ($cart) {
            $cart = $this->saveTid($cartRepo, $cart);
        }
        array_unshift($arguments, $cart);
        return $arguments;
    }
    
    /**
     * Save the tid extension attribute that's attached to the cart
     *
     * @param CartInterface $cart
     * @return CartInterface
     * @throws CouldNotSaveException
     */
    private function saveTid(CartRepositoryInterface $cartRepo, CartInterface $cart)
    {
        $extensionAttributes = $cart->getExtensionAttributes();
        if ($extensionAttributes) {
            $tid = $extensionAttributes->getOracleTid();
            if ($tid) {
                try {
                    $this->tidRepo->save($tid);
                } catch (\Exception $e) {
                    throw new CouldNotSaveException(new Phrase('An exception occurred while trying to save the TID'), $e);
                }
            }
        }
        return $cart;
    }
}

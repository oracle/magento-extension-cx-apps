<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Order\Plugin;

use Oracle\Connector\Api\TidRepositoryInterface;
use Oracle\M2\Connector\SettingsInterface;
use Oracle\M2\Core\Cookie\ReaderInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\PhraseFactory;
use Magento\Sales\Api\Data\OrderExtensionFactory;
use Magento\Quote\Model\Quote;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\StoreManager;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * Class Get
 * @package Oracle\Order\Plugin
 */
class Get
{
    /** @var TidRepositoryInterface */
    protected $tidRepo;

    /** @var TidAttributeFactory */
    protected $tidAttributeFactory;

    /** @var OrderExtensionFactory */
    protected $orderExtensionFactory;

    /** @var SettingsInterface */
    protected $connectorSettings;

    /** @var StoreManager */
    protected $storeManager;

    /** @var ReaderInterface */
    protected $cookieReader;

    /** @var PhraseFactory  */
    protected $phraseFactory;

    /**
     * Get constructor.
     *
     * @param TidRepositoryInterface $tidRepo
     * @param ExtensionAttributesFactory $orderExtensionFactory
     * @param SettingsInterface $connectorSettings
     * @param StoreManager $storeManager
     * @param ReaderInterface $cookieReader
     */
    public function __construct(
        TidRepositoryInterface $tidRepo,
        OrderExtensionFactory $orderExtensionFactory,
        SettingsInterface $connectorSettings,
        StoreManager $storeManager,
        ReaderInterface $cookieReader,
        PhraseFactory $phraseFactory
    ) {
        $this->tidRepo = $tidRepo;
        $this->orderExtensionFactory = $orderExtensionFactory;
        $this->connectorSettings = $connectorSettings;
        $this->storeManager = $storeManager;
        $this->cookieReader = $cookieReader;
        $this->phraseFactory = $phraseFactory;
    }

    /**
     * @param OrderRepositoryInterface $orderRepo
     * @param OrderInterface $order [null]
     * @return OrderInterface|null
     * @throws LocalizedException
     */
    public function afterGet(OrderRepositoryInterface $orderRepo, OrderInterface $order = null)
    {
        if (!$order) {
            return $order;
        }
        return $this->attachTid($orderRepo, $order);
    }

    /**
     * Attach the tid as an extension attribute to the order.
     *
     * Sets and persists the tid object
     *
     * @param OrderRepositoryInterface $orderRepo
     * @param OrderInterface $order
     * @return OrderInterface
     * @throws LocalizedException
     */
    private function attachTid(OrderRepositoryInterface $orderRepo, OrderInterface $order)
    {
        $extensionAttributes = $order->getExtensionAttributes() ?: $this->orderExtensionFactory->create();
        $tid = $extensionAttributes->getOracleTid();
        if (!$tid) {
            try {
                $tid = $this->tidRepo->getByOrderId($order->getId())
                    ?: $this->tidRepo->getByCartId($order->getQuoteId());
            } catch (\Exception $e) {
                throw new LocalizedException(
                    $this->phraseFactory->create(
                        ['text' => 'An exception was thrown while loading the TID: ' . $e->getMessage()]
                    ),
                    $e
                );
            }
        }
        if ($tid) {
            $this->tidRepo->save($tid->setOrderId($order->getEntityId()));
            $order->setExtensionAttributes($extensionAttributes->setOracleTid($tid));
        }
        return $order;
    }
}

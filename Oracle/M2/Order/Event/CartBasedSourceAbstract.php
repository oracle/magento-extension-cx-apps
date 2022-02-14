<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Order\Event;

use Oracle\Connector\Api\Data\TidInterface;
use Oracle\M2\Connector\Event\ContextProviderInterface;
use Oracle\M2\Connector\Event\SourceInterface;
use Oracle\M2\Email\Event\Trigger\Cart;
use Magento\Framework\Api\ExtensibleDataInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Api\Data\OrderInterface;

abstract class CartBasedSourceAbstract implements SourceInterface, ContextProviderInterface
{
    /** @var \Oracle\M2\Connector\SettingsInterface  */
    protected $_connector;
    
    /** @var \Oracle\M2\Order\SettingsInterface  */
    protected $orderHelper;
    
    /** @var \Oracle\M2\Cart\SettingsInterface  */
    protected $cartHelper;
    
    /** @var \Oracle\M2\Core\Cookie\ReaderInterface  */
    protected $_cookie;

    /**
     * @param \Oracle\M2\Connector\SettingsInterface $connector
     * @param \Oracle\M2\Order\SettingsInterface $orderHelper
     * @param \Oracle\M2\Core\Cookie\ReaderInterface $cookie
     */
    public function __construct(
        \Oracle\M2\Connector\SettingsInterface $connector,
        \Oracle\M2\Order\SettingsInterface $orderHelper,
        \Oracle\M2\Cart\SettingsInterface $cartHelper,
        \Oracle\M2\Core\Cookie\ReaderInterface $cookie
    ) {
        $this->_connector = $connector;
        $this->orderHelper = $orderHelper;
        $this->cartHelper = $cartHelper;
        $this->_cookie = $cookie;
    }

    /**
     * @see SourceInterface::transform
     * @param OrderInterface|Quote $salesObject
     */
    public function transform($salesObject)
    {
        $lineItems = [];
        $taxIncluded = $this->cartHelper->isTaxIncluded('store', $salesObject->getStoreId());
        foreach ($salesObject->getAllVisibleItems() as $lineItem) {
            $price = $this->orderHelper->getItemPrice($lineItem, false, $taxIncluded);
            $quantity = ($lineItem->getQtyOrdered() === null) ? $lineItem->getQty() : $lineItem->getQtyOrdered();
            $discountPerItem = ($quantity > 0) ? ($this->orderHelper->getItemDiscount($lineItem) / $quantity) : 0;
            $lineItems[] = [
                'sku' => $lineItem->getSku(),
                'name' => $this->orderHelper->getItemName($lineItem),
                'category' => $this->orderHelper->getItemCategories($lineItem),
                'productUrl' => $this->orderHelper->getItemUrl($lineItem),
                'imageUrl' => $this->orderHelper->getItemImage($lineItem),
                'description' => $this->orderHelper->getItemDescription($lineItem),
                'totalPrice' => $this->orderHelper->getItemRowTotal($lineItem, false, $taxIncluded),
                'quantity' => $quantity,
                'unitPrice' => $price,
                // Average sale price per sub-item, doesn't account for discount specifics
                'salePrice' => $price - ($discountPerItem ?: 0),
                'other' => $this->orderHelper->getItemOtherField($lineItem)
            ];
        }
        $shipment = $this->orderHelper->createShippingItem($salesObject);
        if (!empty($shipment)) {
            $lineItems[] = $shipment;
        }
        $isBase = $this->orderHelper->isBasePrice('store', $salesObject->getStoreId());
        $data = $this->_initializeData($salesObject, $isBase);
        $data += [
            'grandTotal' => $isBase ? $salesObject->getBaseGrandTotal() : $salesObject->getGrandTotal(),
            'subtotal' => $isBase ? $salesObject->getBaseSubtotal() : $salesObject->getSubtotal(),
            'discountAmount' => $this->getDiscountAmount($salesObject, $isBase),
            'taxAmount' => $this->getTaxAmount($salesObject, $isBase),
            'originIp' => $salesObject->getRemoteIp(),
            'lineItems' => $lineItems
        ];
        if ($salesObject instanceof ExtensibleDataInterface) {
            $tid = $this->getTid($salesObject);
            if ($tid) {
                $data['tid'] = $tid;
            }
        }
        return $data;
    }

    /**
     * Implementors will create JSON data to match requset
     *
     * @param mixed $object
     * @param boolean $isBase
     * @return array
     */
    abstract protected function _initializeData($object, $isBase);

    /**
     * Gets the tid value from the tid extension attribute on the order
     *
     * @param ExtensibleDataInterface $order
     * @return int|null
     */
    protected function getTid(ExtensibleDataInterface $order)
    {
        $extensionAttributes = $order->getExtensionAttributes();
        if ($extensionAttributes) {
            /** @var TidInterface $tid */
            $tid = $extensionAttributes->getOracleTid();
            if ($tid) {
                return $tid->getValue();
            }
        }
        return null;
    }

    /**
     * Calculate the discount amount
     *
     * @param OrderInterface|Quote $salesObject
     * @param bool $isBase
     * @return float|int|number
     */
    protected function getDiscountAmount($salesObject, $isBase)
    {
        $discountAmount = 0;
        if ($salesObject instanceof OrderInterface) {
            /** @var OrderInterface $salesObject */
            $discountAmount = abs($isBase ? $salesObject->getBaseDiscountAmount() : $salesObject->getDiscountAmount());
        } else {
            /** @var Quote $salesObject */
            $discountAmount = $isBase
                ? $salesObject->getBaseSubtotal() - $salesObject->getBaseSubtotalWithDiscount()
                : $salesObject->getSubtotal() - $salesObject->getSubtotalWithDiscount();
            $discountAmount = abs($discountAmount);
        }
        return $discountAmount;
    }

    /**
     * Determine the tax amount.
     *
     * @param OrderInterface|Quote $salesObject
     * @param bool $isBase
     * @return float|int|number
     */
    protected function getTaxAmount($salesObject, $isBase)
    {
        $taxAmount = 0;
        if ($salesObject instanceof OrderInterface) {
            $taxAmount = $isBase ? $salesObject->getBaseTaxAmount() : $salesObject->getTaxAmount();
        } else {
            /** @var Quote $salesObject */
            $totals = $salesObject->getTotals();
            if (isset($totals['tax'])) {
                /** @var Quote\Address\Total $taxTotal */
                $taxTotal = $totals['tax'];
                $taxAmount = $taxTotal->getValue();
            }
        }
        return $taxAmount;
    }
}

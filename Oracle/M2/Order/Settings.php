<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Order;

use Oracle\M2\Core\DataObject;
use Magento\Quote\Model\Quote\Item\AbstractItem;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Model\Order;

class Settings extends \Oracle\M2\Core\Config\ContainerAbstract implements SettingsInterface
{
    const SHIPPING_SKU = 'SHIPPING';

    /** @var \Oracle\M2\Connector\SettingsInterface */
    protected $_settings;

    /** @var \Oracle\M2\Core\Config\ScopedInterface */
    protected $_productRepo;

    /** @var \Oracle\M2\Core\Directory\CurrencyManagerInterface */
    protected $currencies;

    /** @var  \Oracle\M2\Core\Directory\Currency|null */
    protected $currency;

    /**
     * Maps to Oracle status `shipped`
     *
     * @var array
     */
    protected $statusShipped = [
        Order::STATE_COMPLETE
    ];

    /**
     * @param \Oracle\M2\Core\Config\ScopedInterface $config
     * @param \Oracle\M2\Connector\SettingsInterface $settings
     * @param \Oracle\M2\Core\Catalog\ProductCacheInterface $productRepo
     */
    public function __construct(
        \Oracle\M2\Core\Config\ScopedInterface $config,
        \Oracle\M2\Connector\SettingsInterface $settings,
        \Oracle\M2\Core\Catalog\ProductCacheInterface $productRepo,
        \Oracle\M2\Core\Directory\CurrencyManagerInterface $currencies
    ) {
        parent::__construct($config);
        $this->_settings = $settings;
        $this->_productRepo = $productRepo;
        $this->currencies = $currencies;
    }

    /**
     * @see parent
     */
    public function isEnabled($scopeType = 'default', $scopeCode = null)
    {
        return $this->_config->isSetFlag(self::XML_PATH_ENABLED, $scopeType, $scopeCode);
    }

    /**
     * @see parent
     */
    public function isIncludeDiscount($scope = 'default', $scopeId = null)
    {
        return (
            !$this->_settings->isOrderService($scope, $scopeId) &&
            $this->_config->isSetFlag(self::XML_PATH_INCLUDE_DISCOUNT, $scope, $scopeId)
        );
    }

    /**
     * @see parent
     */
    public function isIncludeTax($scope = 'default', $scopeId = null)
    {
        return (
            !$this->_settings->isOrderService($scope, $scopeId) &&
            $this->_config->isSetFlag(self::XML_PATH_INCLUDE_TAX, $scope, $scopeId)
        );
    }

    /**
     * @see parent
     */
    public function isIncludeShipping($scope = 'default', $scopeId = null)
    {
          return (
              !$this->_settings->isOrderService($scope, $scopeId) &&
              $this->_config->isSetFlag(self::XML_PATH_INCLUDE_SHIPPING, $scope, $scopeId)
          );
    }

    /**
     * @see parent
     */
    public function isBasePrice($scope = 'default', $scopeId = null)
    {
        return $this->_config->getValue(self::XML_PATH_PRICE, $scope, $scopeId) == 'base';
    }

    /**
     * @see parent
     */
    public function getDescriptionAttribute($scope = 'default', $scopeId = null)
    {
        $attribute = $this->_config->getValue(self::XML_PATH_DESC, $scope, $scopeId);
        if (empty($attribute)) {
            $attribute = 'description';
        }
        return $attribute;
    }

    /**
     * @see parent
     */
    public function getImageAttribute($scope = 'default', $scopeId = null)
    {
        $attribute = $this->_config->getValue(self::XML_PATH_IMAGE, $scope, $scopeId);
        if (empty($attribute)) {
            $attribute = 'image';
        }
        return $attribute;
    }

    /**
     * @see parent
     */
    public function createShippingItem($order)
    {
        $lineItem = [];
        if ($this->isIncludeShipping('store', $order->getStoreId()) && $order->hasShipments()) {
            $descriptions = [];
            foreach ($order->getTracksCollection() as $track) {
                if ($track->hasTrackNumber() && $track->hasTitle()) {
                    $descriptions[] = "{$track->getTitle()} - {$track->getTrackNumber()}";
                }
            }
            $itemObject = new \Oracle\M2\Core\DataObject([
                'parent_item_id' => false,
                'qty_ordered' => 1,
                'store_id' => $order->getStoreId(),
                'row_total' => $order->getShippingAmount(),
                'base_row_total' => $order->getBaseShippingAmount(),
                'tax_amount' => $order->getShippingTaxAmount(),
                'base_tax_amount' => $order->getBaseShippingTaxAmount(),
                'discount_amount' => $order->getShippingDiscountAmount(),
                'base_discount_amount' => $order->getBaseShippingDiscountAmount()
            ]);
            $price = $this->getItemPrice($itemObject);
            $lineItem['sku'] = self::SHIPPING_SKU;
            $lineItem['name'] = $order->getShippingDescription();
            $lineItem['description'] = implode('<br/>', $descriptions);
            $lineItem['quantity'] = 1;
            $lineItem['totalPrice'] = $price;
            $lineItem['salePrice'] = $price;
        }
        return $lineItem;
    }

    /**
     * @see parent
     */
    public function getOrderStatus($scopeType = 'default', $scopeCode = null)
    {
        return $this->_config->getValue(self::XML_PATH_STATUS, $scopeType, $scopeCode);
    }

    /**
     * @see \Oracle\M2\Order\SettingsInterface::isShipped
     */
    public function isShipped(OrderInterface $order)
    {
        return in_array($order->getState(), $this->statusShipped);
    }

    /**
     * @see parent
     */
    public function getImportStatus($scopeType = 'default', $scopeCode = null)
    {
        $imports = $this->_config->getValue(self::XML_PATH_IMPORT, $scopeType, $scopeCode);

        if (is_string($imports)) {
            $imports = explode(',', $imports);
        } else {
            $imports = [];
        }
        return $imports;
    }

    /**
     * @see parent
     */
    public function getDeleteStatus($scopeType = 'default', $scopeCode = null)
    {
        $deletes = $this->_config->getValue(self::XML_PATH_DELETE, $scopeType, $scopeCode);

        if (is_string($deletes)) {
            $deletes = explode(',', $deletes);
        } else {
            $deletes = [];
        }
        return $deletes;
    }

    /**
     * @see parent
     */
    public function getItemImage($lineItem)
    {
        return $this->_productRepo->getImage(
            $this->_product($lineItem, false),
            $this->getImageAttribute('store', $lineItem->getStoreId()),
            $this->_parentProductId($lineItem)
        );
    }

    /**
     * @see parent
     */
    public function getItemOtherField($lineItem)
    {
        $product = $this->_product($lineItem, false);
        if ($product) {
            $otherField = $this->_config->getValue(self::XML_PATH_OTHER, 'store', $lineItem->getStoreId());
            $attribute = $product->getResource()->getAttribute($otherField);
            if ($attribute) {
                $other = $product->getData($otherField);
                if (preg_match('/Image$/', get_class($attribute->getFrontend()))) {
                    return $attribute->getFrontend()->getUrl($product);
                } elseif ($attribute->usesSource()) {
                    $other = $attribute->getSource()->getOptionText($other);
                }

                if (is_array($other)) {
                    $flattenValues = function ($summary, $current) {

                        if (!is_array($current)) {
                            $summary = ($summary !== null)? $summary . ', ' . $current : $current;
                        }

                        return $summary;
                    };

                    $other = array_reduce($other, $flattenValues);
                }

                if ($other === false) {
                    $other = null;
                }
                return $other;
            }
        }
        return null;
    }

    /**
     * @see parent
     */
    public function getItemUrl($lineItem)
    {
        return $this->_productRepo->getUrl($this->getVisibleProduct($lineItem));
    }


    /**
     * @see parent
     */
    public function getItemCategories($lineItem)
    {
        $product = $this->_product($lineItem);
        if ($product) {
            return $this->_productRepo->getCategory($product);
        }
        return null;
    }

    /**
     * @see parent
     */
    public function getItemDescription($lineItem)
    {
        $product = $this->getVisibleProduct($lineItem);
        if ($product) {
            return $this->_productRepo->getDescription($product, $this->getDescriptionAttribute('store', $lineItem->getStoreId()));
        } else {
            return $lineItem->getDescription();
        }
    }

    /**
     * @see parent
     */
    public function getItemName($lineItem)
    {
        return $this->_parent($lineItem)->getName();
    }

    /**
     * @see parent
     * @param \Magento\Sales\Api\Data\OrderItemInterface|\Oracle\M2\Core\DataObject $lineItem
     * @param bool $customerView [false]
     * @param bool $includeTax [false]
     */
    public function getItemPrice($lineItem, $customerView = false, $includeTax = false)
    {
        $parentItem = $this->_parent($lineItem);
        if ($customerView) {
            return $includeTax ? $parentItem->getPriceInclTax() : $parentItem->getPrice();
        } else {
            $storeId = $parentItem->getStoreId();
            $basePrice = $this->isBasePrice('store', $storeId);
            if ($this->_settings->isOrderService('store', $storeId)) {
                if ($basePrice) {
                    return $includeTax ?
                        $parentItem->getBasePriceInclTax() :
                        $parentItem->getBasePrice();
                } else {
                    return $includeTax ?
                        $parentItem->getPriceInclTax() :
                        $parentItem->getPrice();
                }
            } else {
                $quantity = $parentItem->getQtyOrdered();
                $rowTotal = $this->_rowTotal($parentItem, $basePrice);
                return !empty($quantity) ? max((float) ($rowTotal / $quantity), 0.00) : 0.00;
            }
        }
    }

    /**
     * @see parent
     */
    public function getItemDiscount($lineItem, $customerView = false)
    {
        $parentItem = $this->_parent($lineItem);
        if ($customerView) {
            return $parentItem->getDiscountAmount();
        } else {
            $storeId = $parentItem->getStoreId();
            $basePrice = $this->isBasePrice('store', $storeId);
            if ($this->_settings->isOrderService('store', $storeId)) {
                return $basePrice ?
                    $parentItem->getBaseDiscountAmount() :
                    $parentItem->getDiscountAmount();
            } else {
                // Discounts are included in the item price optionally
                return 0.00;
            }
        }
    }

    /**
     * @see parent
     * @param \Magento\Sales\Api\Data\OrderItemInterface|\Oracle\M2\Core\DataObject $lineItem
     * @param bool $customerView [false]
     * @param bool $includeTax [false]
     */
    public function getItemRowTotal($lineItem, $customerView = false, $includeTax = false)
    {
        $parentItem = $this->_parent($lineItem);
        if ($customerView) {
            return $includeTax ?
                $parentItem->getRowTotalInclTax() :
                $parentItem->getRowTotal();
        } else {
            $storeId = $parentItem->getStoreId();
            $basePrice = $this->isBasePrice('store', $storeId);
            if ($this->_settings->isOrderService('store', $storeId)) {
                if ($basePrice) {
                    return $includeTax ?
                        $parentItem->getBaseRowTotalInclTax() :
                        $parentItem->getBaseRowTotal();
                }
                return $includeTax ?
                    $parentItem->getRowTotalInclTax() :
                    $parentItem->getRowTotal();
            } else {
                return $this->_rowTotal($parentItem, $basePrice);
            }
        }
    }

    /**
     * @see parent
     */
    public function getFlatItems($object)
    {
        $index = null;
        $lineItems = [];
        foreach ($object->getAllItems() as $lineItem) {
            // If a parent exists, override previous index
            if ($lineItem->getParentItemId()) {
                $lineItems[$index] = $lineItem;
            } else {
                $lineItems[] = $lineItem;
                if (is_null($index)) {
                    $index = 0;
                } else {
                    $index++;
                }
            }
        }
        return $lineItems;
    }

    /**
     * @see parent
     */
    public function getVisibleProduct($lineItem)
    {
        return $this->_productRepo->getVisibleProduct(
            $lineItem->getProductId(),
            $this->_parentProductId($lineItem),
            $lineItem->getStoreId()
        );
    }

    /**
     * @see SettingsInterface::formatPrice
     * @param float $price
     * @return string
     */
    public function formatPrice($price, $useDisplaySymbol = true)
    {
        if (!is_null($this->currency)) {
            $options = [
                'precision' => 2,
                'display' => $useDisplaySymbol ?
                    \Zend_Currency::USE_SYMBOL :
                    \Zend_Currency::NO_SYMBOL
            ];
            return $this->currency->formatTxt($price, $options);
        }
        return $price;
    }

    /**
     * @see SettingsInterface:setCurrency
     * @param string $code
     * @return self
     */
    public function setCurrency($code)
    {
        $this->currency = $this->currencies->getByCode($code);
        return $this;
    }

    /**
     * @param mixed $lineItem
     * @return mixed
     */
    protected function _product($lineItem, $includeParent = true)
    {
        if ($includeParent) {
            $lineItem = $this->_parent($lineItem);
        }
        return $this->_productRepo->getById($lineItem->getProductId(), $lineItem->getStoreId());
    }

    /**
     * @param  AbstractItem|OrderItemInterface|DataObject $lineItem
     * @return \Magento\Sales\Api\Data\OrderItemInterface
     */
    protected function _parent($lineItem)
    {
        $this->validateLineItem($lineItem);
        try {
            if (!$lineItem->getParentItemId()) {
                return $lineItem;
            }
        } catch (\BadMethodCallException $e) {
            // DataObject that doesn't have a parentItemId
            return $lineItem;
        }
        $parentItem = $lineItem->getParentItem();
        return ($parentItem != null) ? $parentItem : $lineItem;
    }

    /**
     * Gets the product of the parent or null
     *
     * @param mixed $lineItem
     * @return mixed
     */
    protected function _parentProductId($lineItem)
    {
        $parentId = null;
        if ($lineItem->getParentItemId()) {
            $parentId = $lineItem->getParentItem()->getProductId();
        }
        return $parentId;
    }

    /**
     * Adjusts the row total for taxes and discounts
     *
     * @param mixed $parentItem
     * @param boolean $basePrice
     * @return float
     */
    protected function _rowTotal($parentItem, $basePrice)
    {
        $rowTotal = $basePrice ?
            $parentItem->getBaseRowTotal() :
            $parentItem->getRowTotal();
        if ($this->isIncludeTax('store', $parentItem->getStoreId())) {
            $rowTotal += $basePrice ?
                $parentItem->getBaseTaxAmount() :
                $parentItem->getTaxAmount();
        }
        if ($this->isIncludeDiscount('store', $parentItem->getStoreId())) {
            $rowTotal -= $basePrice ?
                $parentItem->getBaseDiscountAmount() :
                $parentItem->getDiscountAmount();
        }
        return $rowTotal;
    }

    /**
     * Determines if the given $lineItem is the expected type
     *
     * @param AbstractItem|OrderItemInterface|DataObject $lineItem
     * @throws \InvalidArgumentException if $lineItem is not of the expected type
     */
    private function validateLineItem($lineItem)
    {
        if (!($lineItem instanceof AbstractItem) &&
            !($lineItem instanceof OrderItemInterface) &&
            !($lineItem instanceof  DataObject)
        ) {
            $message = 'Line item passed must be an instance of \Magento\Quote\Model\Quote\Item\AbstractItem, '
                . '\Magento\Sales\Api\Data\OrderItemInterface, or \Oracle\M2\Core\DataObject. %s given.';
            $type = is_object($lineItem) ? get_class($lineItem) : gettype($lineItem);
            throw new \InvalidArgumentException(sprintf($message, ucfirst($type)));
        }
    }
}

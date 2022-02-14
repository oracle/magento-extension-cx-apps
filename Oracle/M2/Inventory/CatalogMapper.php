<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Inventory;

class CatalogMapper extends \Oracle\M2\Product\CatalogMapperAbstract
{
    private static $_codes = [
        'is_in_stock' => 'Stock Availability',
        'qty' => 'Inventory Quantity',
        'min_qty' => 'Inventory Minimum Quantity',
        'min_sale_qty' => 'Inventory Minimum Sale in Cart',
        'max_sale_qty' => 'Inventory Maximum Sale in Cart',
        'backorders' => 'Backorders'
    ];

    private static $_defaultCodes = [
        'quantity' => 'qty',
        'availability' => 'is_in_stock',
    ];

    private static $_typeCodes = [
        'is_in_stock' => 'boolean',
        'qty' => 'double',
        'min_qty' => 'double',
        'min_sale_qty' => 'double',
        'max_sale_qty' => 'double',
        'backorders' => 'integer',
    ];

    protected $_extension;
    protected $_products;

    /**
     * @param \Oracle\M2\Product\ExtensionAbstract $extension
     * @param \Oracle\M2\Core\Catalog\ProductCacheInterface $products
     * @param \Oracle\M2\Product\CatalogMapperManagerInterface $manager
     */
    public function __construct(
        \Oracle\M2\Product\ExtensionAbstract $extension,
        \Oracle\M2\Core\Catalog\ProductCacheInterface $products,
        \Oracle\M2\Product\CatalogMapperManagerInterface $manager
    ) {
        parent::__construct($manager);
        $this->_products = $products;
        $this->_extension = $extension;
    }

    /**
     * @see parent
     */
    public function getExtraFields()
    {
        return self::$_codes;
    }

    /**
     * @see parent
     */
    public function getDefaultFields()
    {
        return self::$_defaultCodes;
    }

    /**
     * @see parent
     */
    public function getFieldAttributes()
    {
        return self::$_typeCodes;
    }

    /**
     * Push inventory changes on checkout
     *
     * @param mixed $observer
     * @return void
     */
    public function checkoutSubmitAllAfter($observer)
    {
        $quote = $observer->getQuote();
        $storeId = $quote->getStoreId();
        $this->_pushItems($quote->getAllItems(), $storeId);
    }

    /**
     * Revert an inventory changes upon cancelation
     *
     * @param mixed $observer
     * @return void
     */
    public function orderItemCancel($observer)
    {
        $item = $observer->getEvent()->getItem();
        $this->_pushItems([$item], $item->getStoreId());
    }

    /**
     * Adjust inventory based on credit memos
     *
     * @param mixed $observer
     * @return void
     */
    public function creditMemoSaveAfter($observer)
    {
        $products = [];
        $creditmemo = $observer->getEvent()->getCreditmemo();
        foreach ($creditmemo->getAllItems() as $item) {
            if ($this->_pushItem($item, $products, $creditmemo->getStoreId())) {
                $products[$item->getProductId()] = true;
            }
        }
    }

    /**
     * Internal push item
     *
     * @param mixed $item
     * @param array $products
     * @param mixed $storeId
     * @return boolean
     */
    protected function _pushItem($item, $products, $storeId)
    {
        if (array_key_exists($item->getProductId(), $products)) {
            return false;
        }
        $product = $this->_products->getById($item->getProductId(), $storeId);
        $event = new \Oracle\M2\Core\DataObject(['product' => $product]);
        $this->_extension->pushChangesToAll($event);
        return true;
    }

    /**
     * Push an array of products and children
     *
     * @param array $items
     * @param mixed $storeId
     * @return void
     */
    protected function _pushItems($items, $storeId)
    {
        $products = [];
        foreach ($items as $item) {
            $children = $item->getChildrenItems();
            if ($children) {
                foreach ($children as $child) {
                    if ($this->_pushItem($child, $products, $storeId)) {
                        $products[$child->getProductId()] = true;
                    }
                }
            } else {
                if ($this->_pushItem($item, $products, $storeId)) {
                    $products[$item->getProductId()] = true;
                }
            }
        }
    }
}

<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Core\Catalog;

interface ProductCacheInterface
{
    /**
     * Retrieves the product from a local cache pr pulls a fresh one
     *
     * @param mixed $productId
     * @param mixed $storeId
     * @return mixed
     */
    public function getById($productId, $storeId = null);

    /**
     * Retrieves the product by its sku
     *
     * @param string $sku
     * @param mixed $storeId
     * @return mixed
     */
    public function getBySku($sku, $storeId = null);

    /**
     * Retrieves a list of products based on the source object
     *
     * @param \Oracle\M2\Connector\Discovery\Source $source
     * @return mixed
     */
    public function getBySource(\Oracle\M2\Connector\Discovery\Source $source);

    /**
     * Returns a list of product ids that are children to this complex product
     *
     * @param mixed $productId
     * @param mixed $storeId
     * @return mixed
     */
    public function getChildrenIds($productId, $storeId = null);

    /**
     * Grabs the parent product
     * NOTE: This is a pretty expensive operation, so only use if a parent
     *       is not known outside of a quote or order for example
     *
     * @param mixed $productId
     * @param mixed $storeId
     * @return mixed
     */
    public function getParent($productId, $storeId = null);

    /**
     * Grabs the visible product
     *
     * @param mixed $product
     * @param mixed $parentId
     * @param mixed $storeId
     * @return mixed
     */
    public function getVisibleProduct($product, $parentId = null, $storeId = null);

    /**
     * Determines if the product alone is visible
     *
     * @param mixed $product
     * @return boolean
     */
    public function isVisible($product);

    /**
     * Grabs the description of the product
     *
     * @param mixed $product
     * @param string $attribute
     * @param mixed $parentId
     * @return mixed
     */
    public function getDescription($product, $attribute = 'description', $parentId = null);

    /**
     * Gets the product image url
     *
     * @param mixed $product
     * @param string $attribute
     * @param mixed $parentId
     * @return string
     */
    public function getImage($product, $attribute = 'image', $parentId = null);

    /**
     * Gets the product url
     *
     * @param mixed $product
     * @param mixed $parentId
     * @return string
     */
    public function getUrl($product, $parentId = null);
}

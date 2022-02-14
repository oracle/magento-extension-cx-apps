<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Core\Catalog;

use Magento\Store\Model\Store;

abstract class ProductCacheAbstract implements ProductCacheInterface
{
    /** @var ImageHelperInterface  */
    protected $_imageHelper;

    /** @var \Oracle\M2\Core\Store\ManagerInterface  */
    protected $_storeManager;

    /** @var ProductCategoryResolverInterface  */
    protected $_resolver;
    
    /** @var \Magento\Framework\UrlInterface  */
    protected $frontUrlModel;

    /**
     * @param ImageHelperInterface $imageHelper
     * @param \Oracle\M2\Core\Store\ManagerInterface $storeManager
     * @param ProductCategoryResolverInterface $resolver
     */
    public function __construct(
        ImageHelperInterface $imageHelper,
        \Oracle\M2\Core\Store\ManagerInterface $storeManager,
        ProductCategoryResolverInterface $resolver,
        \Magento\Framework\Url $frontUrlModel
    ) {
        $this->_imageHelper = $imageHelper;
        $this->_storeManager = $storeManager;
        $this->_resolver = $resolver;
        $this->frontUrlModel = $frontUrlModel;
    }

    /**
     * @see parent
     */
    public function isVisible($product)
    {
        return $product->getVisibility() != 1;
    }

    /**
     * @see parent
     */
    public function getCategory($product, $resolver = 'single')
    {
        return $this->_resolver->getCategory($product, $resolver);
    }

    /**
     * @see parent
     */
    public function getVisibleProduct($product, $parentId = null, $storeId = null)
    {
        if (is_numeric($product)) {
            $product = $this->getById($product, $storeId);
        }
        if (is_null($product)) {
            return null;
        }
        if ($this->isVisible($product)) {
            return $product;
        }
        return $this->_parent($product, $parentId);
    }

    /**
     * @see parent
     */
    public function getDescription($product, $attribute = 'description', $parentId = null)
    {
        if ($product = $this->getVisibleProduct($product, $parentId)) {
            return $product->getData($attribute);
        }
        return null;
    }

    /**
     * @see parent
     */
    public function getImage($product, $attribute = 'image', $parentId = null)
    {
        if ($product) {
            $image = $this->_imageHelper->getImageUrl($product, $attribute);
            if (preg_match('|/placeholder/|', $image) && $parent = $this->_parent($product, $parentId)) {
                return $this->_imageHelper->getImageUrl($parent, $attribute);
            } else {
                return $image;
            }
        } else {
            return $this->_imageHelper->getDefaultPlaceHolderUrl();
        }
    }

    /**
     * @see parent
     */
    public function getUrl($product, $parentId = null)
    {
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $this->getVisibleProduct($product, $parentId);
        if ($product) {
            $routeParams = [
                '_nosid' => true,
                '_scope' => $product->getStoreId(),
                'id' => $product->getId(),
                's' => $product->getUrlKey()
            ];
            return $this->frontUrlModel->getUrl('catalog/product/view', $routeParams);
        }
        return $this->_storeManager->getStore(true)->getBaseUrl();
    }

    /**
     * Gets the parent product defaulting to parent first
     *
     * @param mixed $product
     * @param mixed $parentId
     * @return mixed
     */
    protected function _parent($product, $parentId = null)
    {
        if (!is_null($parentId)) {
            return $this->getById($parentId, $product->getStoreId());
        } else {
            return $this->getParent($product);
        }
    }
}

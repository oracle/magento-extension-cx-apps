<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Email\Model;

class Context
{
    protected $_cartRepo;
    protected $_wishlistFactory;
    protected $_orderRepo;
    protected $_orderItemRepo;
    protected $_urlBuilder;
    protected $_emailConfig;
    protected $_storeManager;

    /**
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepo
     * @param \Magento\Sales\Model\Order\ItemFactory $orderItemRepo
     * @param \Magento\Quote\Api\CartRepositoryInterface $cartRepo
     * @param \Magento\Wishlist\Model\WishlistFactory $wishlistFactory
     * @param \Magento\Email\Model\Template\Config $emailConfig
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepo,
        \Magento\Sales\Model\Order\ItemFactory $orderItemRepo,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepo,
        \Magento\Wishlist\Model\WishlistFactory $wishlistFactory,
        \Magento\Email\Model\Template\Config $emailConfig,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->_cartRepo = $cartRepo;
        $this->_wishlistFactory = $wishlistFactory;
        $this->_orderRepo = $orderRepo;
        $this->_orderItemRepo = $orderItemRepo;
        $this->_urlBuilder = $urlBuilder;
        $this->_emailConfig = $emailConfig;
        $this->_storeManager = $storeManager;
    }

    /**
     * @return \Magento\Quote\Api\CartRepositoryInterface
     */
    public function getCartRepo()
    {
        return $this->_cartRepo;
    }

    /**
     * @return \Magento\Wishlist\Model\WishlistFactory
     */
    public function getWishlistFactory()
    {
        return $this->_wishlistFactory;
    }

    /**
     * @return \Magento\Sales\Api\OrderRepositoryInterface
     */
    public function getOrderRepo()
    {
        return $this->_orderRepo;
    }

    /**
     * @return \Magento\Sales\Api\OrderItemRepositoryInterface
     */
    public function getOrderItemRepo()
    {
        return $this->_orderItemRepo;
    }

    /**
     * @return \Magento\Framework\UrlInterface
     */
    public function getUrlBuilder()
    {
        return $this->_urlBuilder;
    }

    /**
     * @return \Magento\Email\Model\Template\Config
     */
    public function getEmailConfig()
    {
        return $this->_emailConfig;
    }

    /**
     * @return Magento\Store\Model\StoreManagerInterface
     */
    public function getStoreManager()
    {
        return $this->_storeManager;
    }
}

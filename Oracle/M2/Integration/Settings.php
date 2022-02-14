<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Integration;

class Settings implements SettingsInterface
{
    protected $_carts;
    protected $_popups;
    protected $_coupons;

    /**
     * @param CartSettingsInterface $carts
     * @param PopupSettingsInterface $popups
     * @param CouponSettingsInterface $coupons
     */
    public function __construct(
        CartSettingsInterface $carts,
        PopupSettingsInterface $popups,
        CouponSettingsInterface $coupons
    ) {
        $this->_carts = $carts;
        $this->_popups = $popups;
        $this->_coupons = $coupons;
    }

    /**
     * @see parent
     */
    public function isPopupEnabled($scopeType = 'default', $scopeId = null)
    {
        return $this->_popups->isPopupEnabled($scopeType, $scopeId);
    }

    /**
     * @see parent
     */
    public function getPopupDomains($scopeType = 'default', $scopeId = null)
    {
        return $this->_popups->getPopupDomains($scopeType, $scopeId);
    }

    /**
     * @see parent
     */
    public function isCreateSubscribers($scopeType = 'default', $scopeId = null)
    {
        return $this->_popups->isCreateSubscribers($scopeType, $scopeId);
    }

    /**
     * @see parent
     */
    public function isCouponEnabled($scopeType = 'default', $scopeId = null)
    {
        return $this->_coupons->isCouponEnabled($scopeType, $scopeId);
    }

    /**
     * @see parent
     */
    public function isCartRecoveryEnabled($scopeType = 'default', $scopeId = null)
    {
        return $this->_carts->isCartRecoveryEnabled($scopeType, $scopeId);
    }

    /**
     * @see parent
     */
    public function getCartRecoveryEmbedCode($scopeType = 'default', $scopeId = null)
    {
        return $this->_carts->getCartRecoveryEmbedCode($scopeType, $scopeId);
    }

    /**
     * @see parent
     */
    public function getCartRecoveryEmail($quote)
    {
        return $this->_carts->getCartRecoveryEmail($quote);
    }

    /**
     * @see parent
     */
    public function getRedirectUrl($modelId, $store, $modelType = 'cart')
    {
        return $this->_carts->getRedirectUrl($modelId, $store, $modelType);
    }

    /**
     * @see parent
     */
    public function isShadowDom($scopeType = 'default', $scopeId = null)
    {
        return $this->_carts->isShadowDom($scopeType, $scopeId);
    }

    /**
     * @see parent
     */
    public function generateUUID()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }

    /**
     * @see \Oracle\M2\Integration\CartSettingsInterface::isTaxIncluded
     * @param string $scopeType
     * @param int $scopeId [null]
     * @return bool
     */
    public function isTaxIncluded($scopeType = 'default', $scopeId = null)
    {
        return $this->_carts->isTaxIncluded($scopeType, $scopeId);
    }
}

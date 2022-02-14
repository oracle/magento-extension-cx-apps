<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Integration\Block;

use Magento\Customer\CustomerData\SectionSourceInterface;

class Recovery extends \Magento\Framework\View\Element\Template implements SectionSourceInterface
{
    protected $_helper;
    protected $_orderHelper;
    protected $_checkoutSession;
    protected $_orderFactory;
    protected $_currency;
    protected $_currencyFactory;
    protected $_order;
    protected $_quote;
    protected $_currentStore;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Directory\Model\CurrencyFactory $currencyFactory
     * @param \Oracle\M2\Integration\CartSettingsInterface $helper
     * @param \Oracle\M2\Order\SettingsInterface $orderHelper
     * @param array $data = []
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Directory\Model\CurrencyFactory $currencyFactory,
        \Oracle\M2\Integration\CartSettingsInterface $helper,
        \Oracle\M2\Order\SettingsInterface $orderHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_checkoutSession = $checkoutSession;
        $this->_orderFactory = $orderFactory;
        $this->_currencyFactory = $currencyFactory;
        $this->_helper = $helper;
        $this->_orderHelper = $orderHelper;
        $this->_currentStore = $this->_storeManager->getStore(true);
    }

    /**
     * @see parent
     */
    public function getCartRecoveryEmbedCode()
    {
        return $this->_helper->getCartRecoveryEmbedCode('store', $this->_currentStore);
    }

    /**
     * @return \Magento\Checkout\Model\Session
     */
    public function getCheckout()
    {
        return $this->_checkoutSession;
    }

    /**
     * Gets the active quote or nothing
     *
     * @return \Magento\Sales\Model\Quote
     */
    public function getQuote()
    {
        if (is_null($this->_quote)) {
            $this->_quote = false;
            $quote = $this->getCheckout()->getQuote();
            if ($quote->getId()) {
                $this->_quote = $quote;
            }
        }
        return $this->_quote;
    }

    /**
     * Gets the active order or nothing
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        if (is_null($this->_order)) {
            $this->_order = false;
            $orderId = $this->getCheckout()->getLastOrderId();
            $capturedOrderId = $this->getCheckout()->getCapturedOrderId();
            if (!$this->getQuote() && $orderId && $orderId != $capturedOrderId) {
                $order = $this->_orderFactory->create()->load($orderId);
                if ($order->getId()) {
                    $this->_order = $order;
                    $this->getCheckout()->setCapturedOrderId($orderId);
                }
            }
        }
        return $this->_order;
    }

    /**
     * Whether or not to write to DOM
     *
     * @return boolean
     */
    public function shouldWriteDom()
    {
        return $this->_helper->isShadowDom('store', $this->_currentStore)
            && $this->getSalesObject();
    }

    /**
     * Gets order or quote or nothing
     *
     * @return mixed
     */
    public function getSalesObject()
    {
        if ($this->getOrder()) {
            return $this->getOrder();
        } elseif ($this->getQuote()) {
            return $this->getQuote();
        } else {
            return false;
        }
    }

    /**
     * Gets the cart redirect url
     *
     * @return string
     */
    public function getCheckoutUrl()
    {
        $quote = $this->getQuote();
        if ($quote) {
            return $this->_helper->getRedirectUrl($quote->getId(), $this->_currentStore);
        } else {
            return $this->getUrl('checkout/cart', [ '_secure' => true ]);
        }
    }

    /**
     * @see helper
     */
    public function renderCategories($lineItem)
    {
        return $this->_orderHelper->getItemCategories($lineItem);
    }

    /**
     * @see helper
     */
    public function getDescription($lineItem)
    {
        return $this->_orderHelper->getItemDescription($lineItem);
    }

    /**
     * @see helper
     */
    public function getProductUrl($lineItem)
    {
        return $this->_orderHelper->getItemUrl($lineItem);
    }

    /**
     * @see helper
     */
    public function getOther($lineItem)
    {
        return $this->_orderHelper->getItemOtherField($lineItem);
    }

    /**
     * @see helper
     */
    public function getItemName($lineItem)
    {
        return $this->_orderHelper->getItemName($lineItem);
    }

    /**
     * @see helper
     */
    public function getFlatItems()
    {
        return $this->_orderHelper->getFlatItems($this->getSalesObject());
    }

    /**
     * @see helper
     */
    public function getImage($lineItem)
    {
        return $this->_orderHelper->getItemImage($lineItem);
    }

    /**
     * Gets the qty for a cart or order
     *
     * @param mixed $lineItem
     * @return float
     */
    public function getQty($lineItem)
    {
        if ($lineItem instanceof \Magento\Sales\Model\Order\Item) {
            return $lineItem->getQtyOrdered();
        } else {
            return $lineItem->getQty();
        }
    }

    /**
     * Gets the discount amount applied on the cart or order
     *
     * @return float
     */
    public function getDiscountAmount()
    {
        $object = $this->getSalesObject();
        if ($object instanceof \Magento\Sales\Model\Quote) {
            return $object->getSubtotal() - $object->getSubtotalWithDiscount();
        } else {
            return $object->getDiscountAmount();
        }
    }

    /**
     * Gets the display for the sales object
     *
     * @param mixed $lineItem
     * @return float
     */
    public function getPrice($lineItem)
    {
        $taxIncluded = method_exists($lineItem, 'getStoreId')
            ? $this->_helper->isTaxIncluded('store', $lineItem->getStoreId())
            : false;
        return $this->_orderHelper->getItemPrice($lineItem, true, $taxIncluded);
    }

    /**
     * Gets the display for the original price
     *
     * @param mixed $lineItem
     * @return float
     */
    public function getOriginalPrice($lineItem)
    {
        return $this->getPrice($lineItem);
    }

    /**
     * Gets the display for the row total
     *
     * @param mixed $lineItem
     * @return float
     */
    public function getRowTotal($lineItem)
    {
        $taxIncluded = method_exists($lineItem, 'getStoreId')
            ? $this->_helper->isTaxIncluded('store', $lineItem->getStoreId())
            : false;
        return $this->_orderHelper->getItemRowTotal($lineItem, true, $taxIncluded);
    }

    /**
     * Gets the currency code for the store
     *
     * @return string
     */
    public function getCurrencyCode()
    {
        if ($this->getOrder()) {
            return $this->getOrder()->getOrderCurrencyCode();
        } else {
            return $this->getQuote()->getQuoteCurrencyCode();
        }
    }

    /**
     * Formats the price
     *
     * @param float $price
     * @return string
     */
    public function formatPrice($price)
    {
        if (is_null($this->_currency)) {
            $this->_currency = $this->_currencyFactory->create()
                ->load($this->getCurrencyCode());
        }
        return $this->_currency->formatTxt($price, [
            'precision' => 2,
            'display' => \Zend_Currency::NO_SYMBOL
        ]);
    }

    /**
     * View-Model (Block) data for KnockoutJS AJAX call
     *
     * @return array
     */
    public function getSectionData()
    {
        $recoveryData = [];

        $recoveryData['shouldWriteDom'] = $this->shouldWriteDom();
        if (!$recoveryData['shouldWriteDom']) {
            return $recoveryData;
        }

        $order = $this->getOrder();
        $recoveryData['hasOrderMetadata'] = false;
        if ($order) {
            $recoveryData['hasOrderMetadata'] = true;
            $recoveryData['orderId'] = $order->getIncrementId();
        }

        $quote = $this->getQuote();
        $recoveryData['hasCartMetadata'] = false;
        if ($quote instanceof \Magento\Quote\Model\Quote) {
            $recoveryData['hasCartMetadata'] = true;
            $recoveryData['cartUrl'] = $this->escapeHtml($this->getCheckoutUrl());
            $recoveryData['cartId'] = $this->escapeHtml($quote->getId());
        }

        $customerEmail = $this->getSalesObject()->getCustomerEmail();
        if ($customerEmail) {
            $recoveryData['emailAddress'] =  $this->escapeHtml($customerEmail);
        }

        $flatItems = $this->getFlatItems();
        $recoveryData['lineItemsCount'] = count($flatItems);
        foreach ($this->getFlatItems() as $lineItem) {
            $recoveryData['lineItems'][] = [
                'sku' => $this->escapeHtml($lineItem->getSku()),
                'name' => $this->escapeHtml($this->getItemName($lineItem)),
                'description' => $this->escapeHtml($this->getDescription($lineItem)),
                'category' => $this->escapeHtml($this->renderCategories($lineItem)),
                'url' => $this->escapeHtml($this->getProductUrl($lineItem)),
                'image' => $this->escapeHtml($this->getImage($lineItem)),
                'other' => $this->escapeHtml($this->getOther($lineItem)),
                'unitPrice' => $this->escapeHtml($this->formatPrice($this->getOriginalPrice($lineItem))),
                'salePrice' => $this->escapeHtml($this->formatPrice($this->getPrice($lineItem))),
                'totalPrice' => $this->escapeHtml($this->formatPrice($this->getRowTotal($lineItem))),
                'quantity' => $this->escapeHtml(($this->getQty($lineItem)))
            ];
        }

        $recoveryData['currency'] = $this->escapeHtml($this->getCurrencyCode());
        $recoveryData['discountAmount'] = $this->escapeHtml($this->formatPrice($this->getDiscountAmount()));
        $recoveryData['subtotal'] = $this->escapeHtml($this->formatPrice($this->getSalesObject()->getSubtotal()));
        $recoveryData['taxAmount'] = $this->escapeHtml($this->formatPrice($this->getSalesObject()->getTaxAmount()));
        $recoveryData['grandTotal'] = $this->escapeHtml($this->formatPrice($this->getSalesObject()->getGrandTotal()));

        return $recoveryData;
    }

    /**
     * Gets the attributes of the script tag in the Recovery embed code
     *
     * @return array
     */
    public function getEmbedScriptTagAttributes()
    {
        $allTagsPattern = "/<script\s*([^>]*)>/i";
        $allTagsMatch = [];
        $attributes = [];
        if (preg_match($allTagsPattern, $this->getCartRecoveryEmbedCode(), $allTagsMatch)) {
            $tagSplitPattern = "/\s*([^=]*)=\"([^\s]*)\"/";
            $tagSplitMatch = [];
            if (preg_match_all($tagSplitPattern, $allTagsMatch[1], $tagSplitMatch)) {
                $attributes = array_combine($tagSplitMatch[1], $tagSplitMatch[2]);
            }
        }

        return $attributes;
    }

    /**
     * Gets the inner code of the embed script. i.e. the embed script without the <script> tag
     *
     * @return string
     */
    public function getEmbedScriptCode()
    {
        $codePattern = "/<script.*?>([^<]*?)<\/script>/i";
        $codeMatch = [];
        $code = '';
        if (preg_match($codePattern, $this->getCartRecoveryEmbedCode(), $codeMatch)) {
            $code = str_replace("\n", " ", trim($codeMatch[1]));
            $code = str_replace("\r", " ", $code);
        }

        return $code;
    }
}

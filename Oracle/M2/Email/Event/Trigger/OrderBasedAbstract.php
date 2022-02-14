<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Email\Event\Trigger;

abstract class OrderBasedAbstract extends SourceAbstract
{
    const XML_PATH_PRICE_DISPLAY = 'tax/sales_display/price';
    const XML_PATH_SUBTOTAL_DISPLAY = 'tax/sales_display/subtotal';

    protected $_addressRender;

    /**
     * @param \Oracle\M2\Email\SettingsInterface $settings
     * @param \Oracle\M2\Core\Directory\CurrencyManagerInterface $currencies
     * @param \Oracle\M2\Email\TriggerInterface $trigger
     * @param \Oracle\M2\Order\SettingsInterface $helper
     * @param \Oracle\M2\Core\Config\ScopedInterface $config
     * @param \Oracle\M2\Core\Sales\AddressRenderInterface $addressRender
     * @param array $message
     */
    public function __construct(
        \Oracle\M2\Email\SettingsInterface $settings,
        \Oracle\M2\Core\Directory\CurrencyManagerInterface $currencies,
        \Oracle\M2\Email\TriggerInterface $trigger,
        \Oracle\M2\Order\SettingsInterface $helper,
        \Oracle\M2\Core\Config\ScopedInterface $config,
        \Oracle\M2\Core\Sales\AddressRenderInterface $addressRender,
        array $message
    ) {
        parent::__construct(
            $settings,
            $currencies,
            $trigger,
            $helper,
            $config,
            $message
        );
        $this->_addressRender = $addressRender;
    }

    /**
     * Gets standard API fields for order based emails
     *
     * @param mixed $order
     * @param mixed $store
     * @return array
     */
    protected function _createOrderFields($order, $store)
    {
        $subtotalDisplay = (int)$this->_config->getValue(self::XML_PATH_SUBTOTAL_DISPLAY, 'store', $store);
        $this->_setCurrency($order->getOrderCurrencyCode());
        $subtotal = $this->_formatPrice($order->getSubtotal());
        $fields = [];
        $fields[] = $this->_createField('subtotal', $subtotal);
        $fields[] = $this->_createField('subtotalExclTax', $subtotal);
        if ($subtotalDisplay != 1) {
            $fields[] = $this->_createField('subtotalInclTax', $this->_formatPrice($order->getSubtotalInclTax()));
        } else {
            $fields[] = $this->_createField('subtotalInclTax', $subtotal);
        }

        $shipAddress = 'N/A';
        $shipDescription = 'N/A';
        if ($order->getIsNotVirtual()) {
            $shipAddress = $this->_addressRender->format($order->getShippingAddress(), 'html');
            $shipDescription = $order->getShippingDescription();
        }
        $customerName = $order->getCustomerIsGuest() ?
            $order->getBillingAddress()->getName() :
            $order->getCustomerName();

        $fields[] = $this->_createField('grandTotal', $this->_formatPrice($order->getGrandTotal()));
        $fields[] = $this->_createField('orderIncrementId', $order->getIncrementId());
        $fields[] = $this->_createField('orderCreatedAt', $order->getCreatedAtFormated('long'));
        $fields[] = $this->_createField('orderBillingAddress', $this->_addressRender->format($order->getBillingAddress(), 'html'));
        $fields[] = $this->_createField('orderShippingAddress', $shipAddress);
        $fields[] = $this->_createField('orderShippingDesc', $shipDescription);
        $fields[] = $this->_createField('orderCustomerName', $customerName);
        $fields[] = $this->_createField('orderStatusLabel', $order->getStatusLabel());
        return $fields;
    }
}

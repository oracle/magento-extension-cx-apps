<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Email\Event\Trigger;

class Cart extends SourceAbstract
{
    const XML_PATH_PRICE_DISPLAY = 'tax/cart_display/price';
    const XML_PATH_SUBTOTAL_DISPLAY = 'tax/cart_display/subtotal';
    const XML_PATH_GRANDTOTAL_TAX = 'tax/cart_display/grandtotal';

    protected $_integration;

    /**
     * @param \Oracle\M2\Integration\CartSettingsInterface $integration
     * @param \Oracle\M2\Email\SettingsInterface $settings
     * @param \Oracle\M2\Core\Directory\CurrencyManagerInterface $currencies
     * @param \Oracle\M2\Email\TriggerInterface $trigger
     * @param \Oracle\M2\Order\SettingsInterface $helper
     * @param \Oracle\M2\Core\Config\ScopedInterface $config
     * @param array $message
     */
    public function __construct(
        \Oracle\M2\Integration\CartSettingsInterface $integration,
        \Oracle\M2\Email\SettingsInterface $settings,
        \Oracle\M2\Core\Directory\CurrencyManagerInterface $currencies,
        \Oracle\M2\Email\TriggerInterface $trigger,
        \Oracle\M2\Order\SettingsInterface $helper,
        \Oracle\M2\Core\Config\ScopedInterface $config,
        array $message
    ) {
        parent::__construct($settings, $currencies, $trigger, $helper, $config, $message);
        $this->_integration = $integration;
    }

    /**
     * @see parent
     */
    public function action($quote)
    {
        $originalAction = parent::action($quote);
        if ($quote->getIsActive() == 0 || $quote->getReservedOrderId()) {
            return '';
        }
        return $originalAction;
    }

    /**
     * @see parent
     */
    public function transform($quote)
    {
        $store = $quote->getStore();
        $delivery = $this->_createDelivery(
            $this->_trigger->getCustomerEmail(),
            $store,
            !isset($this->_message['previousMessage']) ?
                $this->_message['sendType'] :
            'triggered'
        );
        $fields = $this->_extraFields(['quote' => $quote]);
        $subtotalDisplay = (int)$this->_config->getValue(self::XML_PATH_SUBTOTAL_DISPLAY, 'store', $store);
        $this->_setCurrency($quote->getQuoteCurrencyCode());
        $fields[] = $this->_createField('subtotal', $this->_formatPrice($quote->getSubtotal()));
        $fields[] = $this->_createField('subtotalExclTax', $this->_formatPrice($quote->getSubtotal()));
        if ($subtotalDisplay != 1) {
            $totals = $quote->getTotals();
            $fields[] = $this->_createField('subtotalInclTax', $this->_formatPrice($totals['subtotal']->getValue()));
        } else {
            $fields[] = $this->_createField('subtotalInclTax', $this->_formatPrice($quote->getSubtotal()));
        }
        if ($this->_config->isSetFlag(self::XML_PATH_GRANDTOTAL_TAX, 'store', $store)) {
            $totals = $quote->getTotals();
            $fields[] = $this->_createField('grandTotal', $this->_formatPrice($totals['grand_total']->getValue()));
        } else {
            $fields[] = $this->_createField('grandTotal', $this->_formatPrice($quote->getGrandTotal()));
        }
        $index = 1;
        $inclTax = (int)$this->_config->getValue(self::XML_PATH_PRICE_DISPLAY, 'store', $store);
        foreach ($this->_helper->getFlatItems($quote) as $lineItem) {
            $fields = array_merge($fields, $this->_createLineItemFields($lineItem, $inclTax, $index));
            $index++;
        }
        $fields[] = $this->_createField('quoteURL', $this->_integration->getRedirectUrl($quote->getId(), $store));
        $delivery['fields'] = $fields;
        return $delivery;
    }
}

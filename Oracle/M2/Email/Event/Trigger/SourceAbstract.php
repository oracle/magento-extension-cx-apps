<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Email\Event\Trigger;

abstract class SourceAbstract implements \Oracle\M2\Connector\Event\SourceInterface
{
    protected $_trigger;
    protected $_message;
    protected $_helper;
    protected $_currencies;
    protected $_currency;
    protected $_config;
    protected $_settings;

    /**
     * @param \Oracle\M2\Email\SettingsInterface $settings
     * @param \Oracle\M2\Core\Directory\CurrencyManagerInterface $currencies
     * @param \Oracle\M2\Email\TriggerInterface $trigger
     * @param \Oracle\M2\Order\SettingsInterface $helper
     * @param \Oracle\M2\Core\Config\ScopedInterface $config
     * @param array $message
     */
    public function __construct(
        \Oracle\M2\Email\SettingsInterface $settings,
        \Oracle\M2\Core\Directory\CurrencyManagerInterface $currencies,
        \Oracle\M2\Email\TriggerInterface $trigger,
        \Oracle\M2\Order\SettingsInterface $helper,
        \Oracle\M2\Core\Config\ScopedInterface $config,
        array $message
    ) {
        $this->_settings = $settings;
        $this->_currencies = $currencies;
        $this->_trigger = $trigger;
        $this->_message = $message;
        $this->_helper = $helper;
        $this->_config = $config;
    }

    /**
     * @see parent
     */
    public function getEventType()
    {
        return 'delivery';
    }

    /**
     * @see parent
     */
    public function action($model)
    {
        return 'add';
    }

    /**
     * @param string $name
     * @param mixed $content
     * @return array
     */
    protected function _createField($name, $content)
    {
        return [
            'name' => $name,
            'content' => $content,
            'type' => 'html'
        ];
    }

    /**
     * Sets the currency to be used in locale formatting
     *
     * @param string $code
     * @return $this
     */
    protected function _setCurrency($code)
    {
        $this->_currency = $this->_currencies->getByCode($code);
        return $this;
    }

    /**
     * Gets a tuple for the sender of this message
     *
     * @param mixed $store
     * @return array
     */
    protected function _sender($store)
    {
        $sender = $this->_message['sender'];
        $senderNamePath = 'trans_email/ident_' . $sender . '/name';
        $senderEmailPath = 'trans_email/ident_' . $sender . '/email';
        return [
            $this->_config->getValue($senderNamePath, 'store', $store->getId()),
            $this->_config->getValue($senderEmailPath, 'store', $store->getId())
        ];
    }

    /**
     * Gets a boilerplat delivery for the store
     *
     * @param string $email
     * @param mixed $store
     * @param string $type
     * @return array
     */
    protected function _createDelivery($email, $store, $type = 'triggered')
    {
        list($fromName, $fromEmail) = $this->_sender($store);
        $delivery = [
            'start' => date('c'),
            'type' => $type,
            'messageId' => $this->_message['messageId'],
            'fromName' => $fromName,
            'fromEmail' => $fromEmail,
            'replyEmail' => empty($this->_message['replyTo']) ?
                $fromEmail :
                $this->_message['replyTo'],
        ];
        foreach ($this->_message['sendFlags'] as $flag) {
            $delivery[$flag] = true;
        }
        $recipients = [
            [
                'id' => $email,
                'type' => 'contact',
                'deliveryType' => 'selected'
            ]
        ];
        if (isset($this->_message['exclusionLists'])) {
            foreach ($this->_message['exclusionLists'] as $listId) {
                $recipients[] = [
                    'id' => $listId,
                    'type' => 'list',
                    'deliveryType' => 'ineligible'
                ];
            }
        }
        $delivery['recipients'] = $recipients;
        return $delivery;
    }

    /**
     * Generates a coupon code from the message
     *
     * @return array
     */
    protected function _extraFields($templateVars = [])
    {
        return $this->_settings->getExtraFields($this->_message, $templateVars, false);
    }

    /**
     * Gets the product based fields for a line item
     *
     * @param mixed $lineItem
     * @param int $inclTax
     * @param mixed $index
     * @return array
     */
    protected function _createLineItemFields($lineItem, $inclTax, $index = null)
    {
        $fields = [];
        $i = is_null($index) ? '' : "_{$index}";
        $productUrl = $this->_helper->getItemUrl($lineItem);
        if (array_key_exists('reviewForm', $this->_message)) {
            $productUrl .= $this->_message['reviewForm'];
        }
        $fields[] = $this->_createField("productId{$i}", $lineItem->getProductId());
        $fields[] = $this->_createField("productName{$i}", $this->_helper->getItemName($lineItem));
        $fields[] = $this->_createField("productSku{$i}", $lineItem->getSku());
        $fields[] = $this->_createField("productImgUrl{$i}", $this->_helper->getItemImage($lineItem));
        $fields[] = $this->_createField("productUrl{$i}", $productUrl);
        $fields[] = $this->_createField("productQty{$i}", number_format(is_null($lineItem->getQtyOrdered()) ? $lineItem->getQty() : $lineItem->getQtyOrdered(), 2));
        $fields[] = $this->_createField("productDescription{$i}", $this->_helper->getItemDescription($lineItem));
        $price = $this->_formatPrice($this->_helper->getItemPrice($lineItem, true));
        $rowTotal = $this->_formatPrice($this->_helper->getItemRowTotal($lineItem, true));
        $fields[] = $this->_createField("productPrice{$i}", $price);
        $fields[] = $this->_createField("productTotal{$i}", $rowTotal);
        $fields[] = $this->_createField("productPriceExclTax{$i}", $price);
        $fields[] = $this->_createField("productTotalExclTax{$i}", $rowTotal);
        if ($inclTax != 1) {
            if ($lineItem->getParentItemId()) {
                $lineItem = $lineItem->getParentItem();
            }
            $fields[] = $this->_createField("productPriceInclTax{$i}", $this->_formatPrice($lineItem->getPriceInclTax()));
            $fields[] = $this->_createField("productTotalInclTax{$i}", $this->_formatPrice($lineItem->getRowTotalInclTax()));
        } else {
            $fields[] = $this->_createField("productPriceInclTax{$i}", $price);
            $fields[] = $this->_createField("productTotalInclTax{$i}", $rowTotal);
        }
        return $fields;
    }

    /**
     * Formats the price using whatever code the price was
     * placed in
     *
     * @param float $price
     * @return string
     */
    protected function _formatPrice($price)
    {
        if (!is_null($this->_currency)) {
            $options = [
                'precision' => 2,
                'display' => $this->_message['displaySymbol'] === false ?
                    \Zend_Currency::NO_SYMBOL :
                    \Zend_Currency::USE_SYMBOL
            ];
            return $this->_currency->formatTxt($price, $options);
        }
        return $price;
    }

    /**
     * Removes duplicate field items based on name. First occurrence will be preserved.
     *
     * @param array $fields
     * @return array
     */
    protected function deDuplicateFields(array $fields)
    {
        $uniqueNames = [];
        $fields = array_filter($fields, function ($field) use (&$uniqueNames) {
            $fieldExists = in_array($field['name'], $uniqueNames);
            if (!$fieldExists) {
                $uniqueNames[] = $field['name'];
            }
            return $fieldExists;
        });
        return array_values($fields);
    }
}

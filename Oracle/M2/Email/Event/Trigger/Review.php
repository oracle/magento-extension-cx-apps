<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Email\Event\Trigger;

class Review extends OrderBasedAbstract
{
    protected $_urls;

    /**
     * @param \Oracle\M2\Email\SettingsInterface $settings
     * @param \Oracle\M2\Core\Directory\CurrencyManagerInterface $currencies
     * @param \Oracle\M2\Email\TriggerInterface $trigger
     * @param \Oracle\M2\Order\SettingsInterface $helper
     * @param \Oracle\M2\Core\Config\ScopedInterface $config
     * @param \Oracle\M2\Core\Sales\AddressRenderInterface $addressRender
     * @param \Oracle\M2\Core\Store\UrlManagerInterface $urls
     * @param array $message
     */
    public function __construct(
        \Oracle\M2\Email\SettingsInterface $settings,
        \Oracle\M2\Core\Directory\CurrencyManagerInterface $currencies,
        \Oracle\M2\Email\TriggerInterface $trigger,
        \Oracle\M2\Order\SettingsInterface $helper,
        \Oracle\M2\Core\Config\ScopedInterface $config,
        \Oracle\M2\Core\Sales\AddressRenderInterface $addressRender,
        \Oracle\M2\Core\Store\UrlManagerInterface $urls,
        array $message
    ) {
        parent::__construct(
            $settings,
            $currencies,
            $trigger,
            $helper,
            $config,
            $addressRender,
            $message
        );
        $this->_urls = $urls;
    }
    /**
     * @see parent
     */
    public function transform($order)
    {
        $store = $order->getStore();
        $delivery = $this->_createDelivery($order->getCustomerEmail(), $store);
        $fields = array_merge(
            $this->_extraFields(['order' => $order]),
            $this->_createOrderFields($order, $store)
        );
        $index = 1;
        $inclTax = (int)$this->_config->getValue(self::XML_PATH_PRICE_DISPLAY, 'store', $store);
        foreach ($this->_helper->getFlatItems($order) as $lineItem) {
            $product = $this->_helper->getVisibleProduct($lineItem);
            foreach ($product->getCategoryIds() as $categoryId) {
                if (isset($this->_message['exclusionCategories'])) {
                    if (in_array($categoryId, $this->_message['exclusionCategories'])) {
                        continue 2;
                    }
                }
                if (!empty($this->_message['categories'])) {
                    if (!in_array($categoryId, $this->_message['categories'])) {
                        continue 2;
                    }
                }
            }
            $reviewUrl = $this->_urls->getFrontendUrl($store, 'review/product/list', [
                '_nosid' => true,
                'id' => $product->getId()
            ]);
            $reviewUrl .= $this->_message['reviewForm'];
            $fields = array_merge($fields, $this->_createLineItemFields($lineItem, $inclTax, $index));
            $fields[] = $this->_createField("reviewUrl_{$index}", $reviewUrl);
            $index++;
        }
        $delivery['fields'] = $this->deDuplicateFields($fields);
        
        return $delivery;
    }
}

<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Email\Event\Trigger;

class Caretip extends OrderBasedAbstract
{
    /**
     * @see parent
     */
    public function transform($lineItem)
    {
        $order = $lineItem->getOrder();
        $store = $order->getStore();
        $inclTax = (int)$this->_config->getValue(self::XML_PATH_PRICE_DISPLAY, 'store', $store);
        $delivery = $this->_createDelivery($order->getCustomerEmail(), $store);
        $fields = array_merge(
            $this->_createOrderFields($order, $store),
            $this->_createLineItemFields($lineItem, $inclTax),
            $this->_extraFields(['order' => $order])
        );
        $fields[] = $this->_createField('content', $this->_message['content']);
        $delivery['fields'] = $fields;
        return $delivery;
    }
}

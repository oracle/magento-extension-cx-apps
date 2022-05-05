<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Order\Event;

class Source extends CartBasedSourceAbstract
{
    /**
     * @see parent
     */
    public function create($order)
    {
        return [
            'uniqueKey' => implode('.', [
                $this->getEventType(),
                $this->action($order),
                $order->getId()
            ])
        ];
    }

    /**
     * @see parent
     */
    public function getEventType()
    {
        return 'order';
    }

    /**
     * @see parent
     */
    public function action($order)
    {
        $orderService = $this->_connector->isOrderService('store', $order->getStoreId());
        //$imports = $this->orderHelper->getImportStatus('store', $order->getStoreId());
        //$deletes = $this->orderHelper->getDeleteStatus('store', $order->getStoreId());
        $imports = array("pending", "complete", "processing");
        $deletes = array("holded", "canceled", "closed");
        if (in_array($order->getStatus(), $imports)) {
            return self::ADD_ACTION;
        } elseif (in_array($order->getStatus(), $deletes)) {
            return self::DELETE_ACTION;
        } elseif ($orderService && $order->getStatus() == 'pending') {
            return self::ADD_ACTION;
        }
        return '';
    }

    /**
     * @see parent
     */
    protected function _initializeData($order, $isBase)
    {
        $data = [
            'emailAddress' => $order->getCustomerEmail(),
            'customer_id' => $order->getCustomerId(),
            'order_id' => $order->getIncrementId(),
            'customerOrderId' => $order->getIncrementId(),
            'states' => [
                'processed' => $this->orderHelper->getOrderStatus('store', $order->getStoreId()) == 'PROCESSED',
                'shipped' => $this->orderHelper->isShipped($order)
            ],
            'orderDate' => date('c', strtotime($order->getCreatedAt())),
            'currency' => $isBase ? $order->getBaseCurrencyCode() : $order->getOrderCurrencyCode(),
            'orderSource' => 'WEBSITE'
        ];
        $data['shippingAmount'] = $isBase ? $order->getBaseShippingAmount() : $order->getShippingAmount();
        if ($order->hasShipments()) {
            foreach ($order->getTracksCollection() as $track) {
                $data['shippingDate'] = date('c', strtotime($track->getCreatedAt()));
                $data['shippingDetails'] = [];
                if ($track->getTitle()) {
                    $data['shippingDetails'][] = $track->getTitle();
                }
                if ($track->getNumber()) {
                    $data['shippingDetails'][] = $track->getNumber();
                }
                $data['shippingDetails'] = implode(': ', $data['shippingDetails']);
                if ($track->getUrl()) {
                    $data['shippingTrackingUrl'] = $track->getUrl();
                }
            }
        }
        return $data;
    }
}

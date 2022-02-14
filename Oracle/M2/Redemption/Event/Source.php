<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Redemption\Event;

class Source implements \Oracle\M2\Connector\Event\SourceInterface
{
    protected $_settings;

    /**
     * @param \Oracle\M2\Order\SettingsInterface $settings
     */
    public function __construct(
        \Oracle\M2\Order\SettingsInterface $settings
    ) {
        $this->_settings = $settings;
    }

    /**
     * @see parent
     */
    public function getEventType()
    {
        return 'couponManager';
    }

    /**
     * @see parent
     */
    public function action($order)
    {
        $couponCode = $order->getCouponCode();
        return !empty($couponCode) ? 'add' : '';
    }

    /**
     * @see parent
     */
    public function transform($order)
    {
        $isBase = $this->_settings->isBasePrice('store', $order->getStoreId());
        return [
            'redemptions' => [
                [
                    'email' => $order->getCustomerEmail(),
                    'coupon' => $order->getCouponCode(),
                    'orderId' => $order->getIncrementId(),
                    'orderSubtotal' => $isBase ?
                        $order->getBaseSubtotal() :
                        $order->getSubtotal(),
                    'orderDiscount' => $isBase ?
                        $order->getBaseDiscountAmount() :
                        $order->getDiscountAmount(),
                    'date' => date('c', strtotime($order->getCreatedAt()))
                ]
            ]
        ];
    }
}

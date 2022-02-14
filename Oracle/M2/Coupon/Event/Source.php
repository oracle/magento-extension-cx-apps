<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Coupon\Event;

class Source implements \Oracle\M2\Connector\Event\SourceInterface
{
    /**
     * @see parent
     */
    public function action($coupons)
    {
        try {
            return $coupons->getCampaignId() ? 'add' : '';
        } catch (\Exception $e) {
            return '';
        }
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
    public function transform($coupons)
    {
        return [
            'campaignId' => $coupons->getCampaignId(),
            'coupons' => $coupons->getCoupons()
        ];
    }
}

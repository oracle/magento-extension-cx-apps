<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Redemption;

abstract class ExtensionAbstract extends \Oracle\M2\Connector\Discovery\ExtensionPushEventAbstract
{
    /**
     * Adds an API dropdown to the integration extension
     *
     * @param mixed $observer
     * @return void
     */
    public function integrationAdditional($observer)
    {
        $observer->getEndpoint()->addFieldToExtension('coupon_manager', [
            'id' => 'toggle_api',
            'name' => 'Type',
            'type' => 'select',
            'position' => 3,
            'typeProperties' => [
                'default' => 'api',
                'options' => [
                    [
                        'id' => 'api',
                        'name' => 'API'
                    ],
                    [
                        'id' => 'js',
                        'name' => 'JavaScript'
                    ]
                ]
            ]
        ]);
    }

    /**
     * @see parent
     */
    protected function _getObject($observer)
    {
        return $observer->getOrder();
    }
}

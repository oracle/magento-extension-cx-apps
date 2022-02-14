<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Product\Event;

use Magento\Catalog\Model\Product\Attribute\Source\Status as MagentoProductStatus;

class Source implements \Oracle\M2\Connector\Event\SourceInterface, \Oracle\M2\Connector\Event\ContextProviderInterface
{

    const EVENT_TYPE = 'product';

    const ACTION_REPLACE = 'replace';
    const ACTION_DELETE = 'delete';

    const KEY_ACTION_REPLACE = 'r';
    const KEY_ACTION_DELETE = 'd';

    protected $_helper;

    protected static $uniqueKeyActionValue = [
        self::ACTION_REPLACE => self::KEY_ACTION_REPLACE,
        self::ACTION_DELETE => self::KEY_ACTION_DELETE
    ];

    /**
     * @param \Oracle\M2\Product\SettingsInterface $helper
     */
    public function __construct(
        \Oracle\M2\Product\SettingsInterface $helper
    ) {
        $this->_helper = $helper;
    }

    /**
     * @see parent
     */
    public function create($product)
    {
        return [
            'sku' => $product->getSku(),
            'scopes' => $product->hasScopes() ? $product->getScopes() : [],
            'uniqueKey' => implode('.', [
                $this->getEventType(),
                self::$uniqueKeyActionValue[$this->action($product)],
                $product->getId(),
                $product->getStoreId()
            ])
        ];
    }

    /**
     * @see parent
     */
    public function getEventType()
    {
        return self::EVENT_TYPE;
    }

    /**
     * @see parent
     */
    public function action($product)
    {
        return ($product->isDeleted() || $product->getStatus() == MagentoProductStatus::STATUS_DISABLED) ?
            self::ACTION_DELETE :
            self::ACTION_REPLACE;
    }

    /**
     * @see parent
     */
    public function transform($product)
    {
        return ['fields' => $this->_helper->getFieldMapping($product)];
    }
}

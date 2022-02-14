<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Contact\Model;

class Settings extends \Oracle\M2\Contact\SettingsAbstract
{
    /** @var \Magento\Customer\Model\ResourceModel\Attribute\CollectionFactory */
    protected $_attributeFactory;

    /** @var \Magento\Customer\Model\ResourceModel\Address\Attribute\CollectionFactory */
    protected $_addressFactory;

    /**
     * @param \Oracle\M2\Core\Store\ManagerInterface $storeManager
     * @param \Oracle\M2\Core\Customer\GroupCacheInterface $groupCache
     * @param \Oracle\M2\Core\Config\FactoryInterface $data
     * @param \Oracle\M2\Core\Config\ScopedInterface $config
     * @param \Oracle\M2\Core\Event\ManagerInterface $events
     * @param \Magento\Customer\Model\ResourceModel\Attribute\CollectionFactory $attributeFactory
     * @param \Magento\Customer\Model\ResourceModel\Address\Attribute\CollectionFactory $addressFactory
     */
    public function __construct(
        \Oracle\M2\Core\Store\ManagerInterface $storeManager,
        \Oracle\M2\Core\Customer\GroupCacheInterface $groupCache,
        \Oracle\M2\Core\Config\FactoryInterface $data,
        \Oracle\M2\Core\Config\ScopedInterface $config,
        \Oracle\M2\Core\Event\ManagerInterface $events,
        \Magento\Customer\Model\ResourceModel\Attribute\CollectionFactory $attributeFactory,
        \Magento\Customer\Model\ResourceModel\Address\Attribute\CollectionFactory $addressFactory
    ) {
        parent::__construct($storeManager, $groupCache, $data, $config, $events);
        $this->_attributeFactory = $attributeFactory;
        $this->_addressFactory = $addressFactory;
    }

    /**
     * @see parent
     */
    public function getAttributeLabels()
    {
        $attributes = [];
        $attributes[] = 'Attributes';
        $attributes[] = 'Shipping Address Attributes';
        $attributes[] = 'Billing Address Attributes';
        return array_combine(self::$_attributeKeys, $attributes);
    }

    /**
     * @see parent
     */
    public function getAttributes()
    {
        $addressAttributes = $this->_addressFactory->create();
        $attributes = [];
        $attributes[] = $this->_attributeFactory->create();
        $attributes[] = $addressAttributes;
        $attributes[] = $addressAttributes;
        return array_combine(self::$_attributeKeys, $attributes);
    }
}

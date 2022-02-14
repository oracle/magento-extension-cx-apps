<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Browse;

abstract class ExtensionAbstract extends \Oracle\M2\Connector\Discovery\ExtensionPushEventAbstract implements \Oracle\M2\Connector\Discovery\TransformEventInterface
{
    /** @var \Oracle\M2\Core\Catalog\ProductCacheInterface */
    protected $_productRepo;

    /**
     * @param \Oracle\M2\Core\Catalog\ProductCacheInterface $productRepo
     * @param \Oracle\M2\Core\Store\ManagerInterface $storeManager
     * @param \Oracle\M2\Connector\QueueManagerInterface $queueManager
     * @param \Oracle\M2\Connector\SettingsInterface $connectorSettings
     * @param \Oracle\M2\Cart\SettingsInterface $helper
     * @param \Oracle\M2\Connector\Event\PlatformInterface $platform
     * @param \Oracle\M2\Connector\Event\SourceInterface $source
     */
    public function __construct(
        \Oracle\M2\Core\Catalog\ProductCacheInterface $productRepo,
        \Oracle\M2\Core\Store\ManagerInterface $storeManager,
        \Oracle\M2\Connector\QueueManagerInterface $queueManager,
        \Oracle\M2\Connector\SettingsInterface $connectorSettings,
        \Oracle\M2\Browse\SettingsInterface $helper,
        \Oracle\M2\Connector\Event\PlatformInterface $platform,
        \Oracle\M2\Connector\Event\SourceInterface $source
    ) {
        parent::__construct(
            $storeManager,
            $queueManager,
            $connectorSettings,
            $helper,
            $platform,
            $source
        );
        $this->_productRepo = $productRepo;
    }

    /**
     * @see parent
     */
    public function transformEvent($observer)
    {
        $data = [];
        $transform = $observer->getTransform();
        $event = $transform->getContext();
        $object = new \Oracle\M2\Core\DataObject(['context' => $event]);
        if (array_key_exists('product_id', $event)) {
            $product = $this->_productRepo->getById($event['product_id'], $event['store_id']);
            if ($product) {
                $object->setProduct($product);
            }
        }
        $transform->setBrowse($this->_source->transform($object));
    }

    /**
     * Adds a Browse ecovery section to the the Integrations
     *
     * @return void
     */
    public function integrationAdditional($observer)
    {
        $observer->getEndpoint()->addExtension([
            'sort_order' => 4,
            'definition' => [
                'id' => 'browse_recovery',
                'name' => 'Browse Recovery',
                'fields' => [
                    [
                        'id' => 'enabled',
                        'name' => 'Enabled',
                        'required' => true,
                        'type' => 'boolean',
                        'typeProperties' => [
                            'default' => false,
                        ]
                    ],
                    [
                        'id' => 'site',
                        'name' => 'Site',
                        'required' => true,
                        'type' => 'select',
                        'depends' => [ [ 'id' => 'enabled', 'values' => [ true ] ] ],
                        'typeProperties' => [ 'oracle' => [ 'type' => 'browseRecovery' ] ]
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
        $event = $observer->getEvent();
        $event->setStoreId($this->_storeManager->getStore()->getId());
        return $event;
    }
}

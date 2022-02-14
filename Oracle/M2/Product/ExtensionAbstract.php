<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Product;

use Oracle\M2\Core\DataObject;
use Magento\Catalog\Model\Product\Attribute\Source\Status as MagentoProductStatus;

abstract class ExtensionAbstract extends \Oracle\M2\Connector\Discovery\AdvancedExtensionAbstract implements \Oracle\M2\Connector\Discovery\GroupInterface, CatalogMapperInterface, \Oracle\M2\Connector\Discovery\TransformEventInterface
{
    private static $fixedMappings = [
        'product_id',
        'parent_product_id'
    ];

    /** @var array */
    private static $requiredCodes = [
        'product_id',
        'title'
    ];

    private static $_defaultCodes = [
        'product_id' => ['sku' => 'SKU'],
        'parent_product_id' => ['Parent ID'],
        'product_category' => ['Category'],
        'price' => ['price' => 'Price'],
        'sale_price' => ['special_price' => 'Special Price'],
        'product_url' => ['URL'],
        'image_url' => ['image' => 'Base Image'],
        'title' => ['name' => 'Name'],
        'description' => ['description' => 'Description'],
        'Sale_Price_Effective_End_Date' => ['special_to_date' => 'Special Price To Date'],
        'Sale_Price_Effective_Start_Date' => ['special_from_date' => 'Special Price From Date'],
    ];

    private static $_remainingCodes = [
        'age_group' => ['Age Group'],
        'availability_date' => ['Availability Date'],
        'brand' => ['Brand'],
        'color' => ['Color'],
        'condition' => ['Condition'],
        'gtin' => ['GTIN'],
        'gender' => ['Gender'],
        'isbn' => ['ISBN'],
        'inventory_threshold' => ['Inventory Threshold'],
        'mpn' => ['MPN'],
        'margin' => ['Margin'],
        'mobile_url' => ['Mobile URL'],
        'size' => ['Size'],
        'upc' => ['UPC']
    ];

    private static $_specialCodes = [
        'parent_product_id' => true,
        'product_category' => true,
        'product_url' => true,
        'product_category_tree' => true,
        'product_category_all_lowest' => true,
        'product_category_first_lowest' => true
    ];

    private static $_customFields = [
        'product_category_tree' => 'Product Category Tree',
        'product_category_all_lowest' => 'All Lowest Product Categories',
        'product_category_first_lowest' => 'First Lowest Product Category'
    ];

    /** @var \Oracle\M2\Connector\MiddlewareInterface */
    protected $_middleware;

    /** @var \Oracle\M2\Connector\RegistrationManagerInterface */
    protected $_registrations;

    /** @var \Oracle\M2\Core\Catalog\ProductCacheInterface */
    protected $_productRepo;

    /** @var \Oracle\M2\Core\Catalog\ProductAttributeCacheInterface */
    protected $_attributes;

    /**
     * @param \Oracle\M2\Core\Catalog\ProductAttributeCacheInterface $attributes
     * @param \Oracle\M2\Connector\MiddlewareInterface $middleware
     * @param \Oracle\M2\Connector\RegistrationManagerInterface $registrations
     * @param \Oracle\M2\Core\Catalog\ProductCacheInterface $productRepo
     * @param \Oracle\M2\Core\App\EmulationInterface $appEmulation
     * @param \Oracle\M2\Core\Store\ManagerInterface $storeManager
     * @param \Oracle\M2\Connector\QueueManagerInterface $queueManager
     * @param \Oracle\M2\Connector\SettingsInterface $connectorSettings
     * @param \Oracle\M2\Connector\Event\HelperInterface $helper
     * @param \Oracle\M2\Connector\Event\PlatformInterface $platform
     * @param \Oracle\M2\Connector\Event\SourceInterface $source
     * @param \Magento\Framework\Filesystem\DriverInterface $fileSystemDriver
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Oracle\M2\Core\Catalog\ProductAttributeCacheInterface $attributes,
        \Oracle\M2\Connector\MiddlewareInterface $middleware,
        \Oracle\M2\Connector\RegistrationManagerInterface $registrations,
        \Oracle\M2\Core\Catalog\ProductCacheInterface $productRepo,
        \Oracle\M2\Core\App\EmulationInterface $appEmulation,
        \Oracle\M2\Core\Store\ManagerInterface $storeManager,
        \Oracle\M2\Connector\QueueManagerInterface $queueManager,
        \Oracle\M2\Connector\SettingsInterface $connectorSettings,
        \Oracle\M2\Connector\Event\HelperInterface $helper,
        \Oracle\M2\Connector\Event\PlatformInterface $platform,
        \Oracle\M2\Connector\Event\SourceInterface $source,
        \Magento\Framework\Filesystem\DriverInterface $fileSystemDriver,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::__construct(
            $appEmulation,
            $storeManager,
            $queueManager,
            $connectorSettings,
            $helper,
            $platform,
            $source,
            $fileSystemDriver,
            $logger
        );
        $this->_attributes = $attributes;
        $this->_middleware = $middleware;
        $this->_registrations = $registrations;
        $this->_productRepo = $productRepo;
    }

    /**
     * @see parent
     */
    public function getSortOrder()
    {
        return 25;
    }

    /**
     * @see parent
     */
    public function getEndpointId()
    {
        return 'product';
    }

    /**
     * @see parent
     */
    public function getEndpointName()
    {
        return 'Products';
    }

    /**
     * @see parent
     */
    public function getEndpointIcon()
    {
        return 'mage-icon-products';
    }

    /**
     * Iterate over all of the registrations and push
     *
     * @param DataObject $event
     * @return void
     */
    public function pushChangesToAll(DataObject $event)
    {
        $event = clone $event;
        $product = $event->getProduct();
        if (!$product) {
            return;
        }

        $siteHash = [];
        /** @var \Oracle\Connector\Model\Registration $registration */
        foreach ($this->_registrations->getAll() as $registration) {
            if (array_key_exists($registration->getConnectorKey(), $siteHash)) {
                continue;
            }

            $defaultStoreId = $this->_middleware->defaultStoreId($registration->getScope(), $registration->getScopeId());
            $scopes = $this->_helper->getEnabledStores($registration->getScope(), $registration->getScopeId());

            $originalSku = $product->getOrigData('sku');
            if (!is_null($originalSku) && $product->dataHasChangedFor('sku')) {
                $archivalProduct = new \Oracle\M2\Core\DataObject(
                    [
                    'id' => $product->getId(),
                    'store_id' => $defaultStoreId,
                    'scopes' => $scopes,
                    'sku' => $originalSku,
                    'status' => MagentoProductStatus::STATUS_DISABLED
                    ]
                );
                $archivalObserver = new \Oracle\M2\Core\DataObject(
                    [
                        'product' => $archivalProduct
                    ]
                );
                $this->pushChanges($archivalObserver);
            }
            $product->setStoreId($defaultStoreId);
            $product->setScopes($scopes);
            $this->pushChanges($event);
            $siteHash[$registration->getConnectorKey()] = $registration;
        }
    }

    /**
     * @see parent
     */
    public function transformEvent($observer)
    {
        $data = [];
        $transform = $observer->getTransform();
        $event = $transform->getContext();
        $product = $this->_productRepo->getById($event['id'], $event['storeId']);

        if ($product) {
            $product->setScopes($event['scopes']);
            $data = $this->_source->transform($product);
        }

        if (isset($event['sku']) && $event['sku']) {
            $data['fields']['sku'] = $event['sku'];
        }

        $transform->setProduct($data);
    }

    /**
     * Handle the endpoint page through products
     *
     * @param mixed $observer
     * @return void
     */
    public function sendProductUpdates($observer)
    {
        $results = [];
        $registration = $observer->getScript()->getRegistration();
        $mappings = $this->_helper->getAll($this->_middleware->defaultStoreId($registration->getScope(), $registration->getScopeId()));
        foreach ($this->_helper->getFieldAttributes($registration) as $field) {
            if (array_key_exists($field['attribute_code'], $mappings)) {
                $field['id'] = $mappings[$field['attribute_code']];
                $results[] = ['context' => $field];
            }
        }
        $observer->getScript()->setResults($results);
    }

    /**
     * @see parent
     */
    public function advancedAdditional($observer)
    {
        $observer->getEndpoint()->addOptionToScript("test", "jobName", [
            'id' => 'test_' . $this->getEndpointId(),
            'name' => 'Product'
        ]);

        $observer->getEndpoint()->addFieldToScript('test', [
            'id' => 'productSku',
            'name' => 'Product SKU',
            'type' => 'text',
            'position' => 15,
            'depends' => [
                [
                    'id' => 'jobName',
                    'values' => ['test_'.$this->getEndpointId()]
                ]
            ]
        ]);

        $observer->getEndpoint()->addOptionToScript('historical', 'jobName', [
            'id' => $this->getEndpointId(),
            'name' => $this->getEndpointName()
        ]);

        $observer->getEndpoint()->addOptionToScript('event', 'jobName', [
            'id' => 'triggerCatalogUpload',
            'name' => 'Sync Product Catalog Fields',
        ]);

        $observer->getEndpoint()->addOptionToScript('event', 'moduleSettings', [
            'id' => $this->getEndpointId(),
            'name' => $this->getEndpointName()
        ]);
    }

    /**
     * @see parent
     */
    public function gatherEndpoints($observer)
    {
        $observer->getDiscovery()->addGroupHelper($this);
    }

    /**
     * @see parent
     */
    public function endpointInfo($observer)
    {
        $scopeFields = [];
        $registration = $observer->getRegistration();
        foreach ($this->_middleware->storeScopes($registration) as $storeId) {
            $store = $this->_storeManager->getStore($storeId);
            $scopeFields[] = [
                'id' => $store->getCode(),
                'name' => $store->getName(),
                'type' => 'boolean',
                'required' => true,
                'typeProperties' => [ 'default' => false ]
            ];
        }

        $delimiters = [];
        foreach ([',' => 'Comma', '|' => 'Pipe', ';' => 'Semi-colon'] as $id => $name) {
            $delimiters[] = [ 'id' => $id, 'name' => $name ];
        }
        $levels = [];
        $numberToNames = ['', 'Second ', 'Third ', 'Fourth '];
        foreach (range(1, 4) as $number) {
            $name = $numberToNames[$number - 1] . 'Highest Subcategory';
            $levels[] = [ 'id' => 'highest-' . $number, 'name' => $name ];
        }
        $levels[] = [ 'id' => 'lowest', 'name' => 'Lowest Subcategory' ];
        $observer->getEndpoint()->addExtension([
            'id' => 'settings',
            'name' => 'Settings',
            'fields' => [
                [
                    'id' => 'enabled',
                    'name' => 'Enabled',
                    'type' => 'boolean',
                    'required' => true,
                    'typeProperties' => [ 'default' => false ]
                ],
                [
                    'id' => 'categoryFormat',
                    'name' => 'Category Format',
                    'type' => 'select',
                    'required' => true,
                    'typeProperties' => [
                        'default' => 'urlKey',
                        'options' => [
                            [ 'id' => 'urlKey', 'name' => 'URL Key' ],
                            [ 'id' => 'name', 'name' => 'Name' ]
                        ]
                    ]
                ],
                [
                    'id' => 'categoryDelimiter',
                    'name' => 'Category Leaf Delimiter',
                    'type' => 'select',
                    'required' => true,
                    'typeProperties' => [
                        'default' => 'space',
                        'options' => array_merge([['id' => 'space', 'name' => 'Space']], $delimiters)
                    ]
                ],
                [
                    'id' => 'categoryBranchDelimiter',
                    'name' => 'Category Branch Delimiter',
                    'type' => 'select',
                    'required' => true,
                    'typeProperties' => [
                        'default' => ',',
                        'options' => $delimiters
                    ]
                ],
                [
                    'id' => 'categorySpecificity',
                    'name' => 'Category Level',
                    'type' => 'select',
                    'required' => true,
                    'typeProperties' => [
                        'default' => 'lowest',
                        'options' => $levels
                    ],
                ],
                [
                    'id' => 'categoryBroadness',
                    'name' => 'Category Level Tiebreaker',
                    'type' => 'select',
                    'required' => true,
                    'depends' => [ [ 'id' => 'categorySpecificity', 'values' => [ 'highest-1', 'highest-2', 'highest-3', 'highest-4' ] ] ],
                    'typeProperties' => [
                        'default' => 'most',
                        'options' => [
                            [ 'id' => 'most', 'name' => 'Most Children' ],
                            [ 'id' => 'least', 'name' => 'Least Children' ]
                        ]
                    ]
                ]
            ]
        ]);

        if (!empty($scopeFields)) {
            $observer->getEndpoint()->addExtension([
                'id' => 'scopes',
                'name' => 'Scopes',
                'fields' => $scopeFields
            ]);
        }

        $defaultFields = [];
        $customFields = [];
        $defaultOptions = $this->_helper->getDefaultFields($registration);
        foreach ($this->_helper->getCustomFields($registration) as $option) {
            if (array_key_exists($option['id'], $defaultOptions)) {
                continue;
            }
            $option['name'] = $this->generateOptionName($option['name'], $option['id']);
            $customFields[] = $option;
        }
        foreach ($defaultOptions as $defaultId => $field) {
            $selectedId = null;
            foreach ($field as $fieldId => $fieldLabel) {
                $selectedId = $fieldId == 'none' ? null : $fieldId;
                if (empty($fieldId)) {
                    $selectedId = $defaultId;
                }
            }
            $typeProperties = [];
            if ($selectedId) {
                $typeProperties['options'] = array_unique(
                    array_merge(
                        [['id' => $selectedId, 'name' => $this->generateOptionName($fieldLabel, $selectedId)]],
                        $customFields
                    ),
                    SORT_REGULAR
                );
            } else {
                $typeProperties['options'] = $customFields;
            }
            $defaultFields[] = [
                'id' => $defaultId,
                'name' => $defaultId,
                'type' => 'select',
                'required' => in_array($defaultId, self::$requiredCodes),
                'typeProperties' => $typeProperties
            ];
        }

        $observer->getEndpoint()->addExtension([
            'id' => 'default_fields',
            'name' => 'Default Fields',
            'fields' => $defaultFields
        ]);

        $observer->getEndpoint()->addObject([
            'id' => 'custom_fields',
            'name' => 'Mapping',
            'shortName' => 'Mapping',
            'identifiable' => true,
            'fields' => [
                [
                    'id' => 'attribute',
                    'name' => 'Product Attribute',
                    'type' => 'select',
                    'typeProperties' => [
                        'options' => $customFields
                    ]
                ]
            ]
        ]);

        $observer->getEndpoint()->addAutoConfigData(
            $this->getEndpointId(),
            $observer->getRegistration()->getScopeHash(),
            $this->getAutoConfigData('product')
        );
    }

    /**
     * @see parent
     */
    public function setExtraFields($observer)
    {
        $product = $observer->getProduct();
        $mappings = $observer->getMappings();
        $extra = $observer->getContainer()->getFields();
        $extra['product_url'] = $this->_productRepo->getUrl($product);
        $extra['parent_product_id'] = '';
        $parent = $this->_productRepo->getParent($product->getId());
        if ($parent) {
            $extra['parent_product_id'] = $parent->getSku();
        }
        $extra['product_category'] = $this->_productRepo->getCategory($product);
        if (array_key_exists('product_category_tree', $mappings)) {
            $extra['product_category_tree'] = $this->_productRepo->getCategory($product, 'tree');
        }
        if (array_key_exists('product_category_all_lowest', $mappings)) {
            $extra['product_category_all_lowest'] = $this->_productRepo->getCategory($product, 'all_leaves');
        }
        if (array_key_exists('product_category_first_lowest', $mappings)) {
            $extra['product_category_first_lowest'] = $this->_productRepo->getCategory($product, 'first_lowest');
        }
        $observer->getContainer()->setFields($extra);
    }

    /**
     * @see parent
     */
    public function setDefaultFields($observer)
    {
        $fields = $observer->getContainer()->getFields();
        $observer->getContainer()->setFields($fields + self::$_defaultCodes + self::$_remainingCodes);
    }

    /**
     * @see parent
     */
    public function setFieldAttributes($observer)
    {
        $fields = $observer->getContainer()->getFields();
        foreach ($this->_attributes->getCollection() as $attribute) {
            $fields[] = [
                'id' => $attribute->getAttributeCode(),
                'attribute_code' => $attribute->getAttributeCode(),
                'frontend_label' => $attribute->getFrontendLabel(),
                'frontend_type' => $attribute->getFrontendInput(),
                'is_global' => (bool)$attribute->getIsGlobal()
            ];
        }
        foreach (self::$_specialCodes as $code => $isGlobal) {
            if (array_key_exists($code, self::$_defaultCodes)) {
                $label = self::$_defaultCodes[$code][0];
            } else {
                $label = self::$_customFields[$code];
            }
            $fields[] = [
                'attribute_code' => $code,
                'frontend_label' => $label,
                'frontend_type' => 'text',
                'is_global' => $isGlobal
            ];
        }
        $observer->getContainer()->setFields($fields);
    }

    /**
     * @see parent
     */
    public function setCustomFields($observer)
    {
        $fields = $observer->getContainer()->getFields();
        $fields = array_merge($fields, $this->_attributes->getOptionArray());
        foreach (self::$_customFields as $id => $name) {
            $fields[] = [ 'id' => $id, 'name' => $name ];
        }
        $observer->getContainer()->setFields($fields);
    }

    /**
     * Implementors will create a product collection that
     * can be filtered down according to user input
     *
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    abstract protected function _collection();

    /**
     * @see parent
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    protected function _sendHistorical($registration, $data)
    {
        $products = $this->_collection();
        if (array_key_exists('startTime', $data)) {
            $startTime = $data['startTime'];
            if ($startTime) {
                $products->addFieldToFilter('updated_at', ['gt' => $startTime]);
            }
        }
        if (array_key_exists('endTime', $data)) {
            $endTime = $data['endTime'];
            if ($endTime) {
                $products->addFieldToFilter('updated_at', ['lt' => $endTime]);
            }
        }
        return $this->_attachScopeFilter($registration, $products);
    }

    /**
     * Attach scope filter based on the registration for the
     * products coming in
     *
     * @param \Oracle\M2\Connector\RegistrationInterface $registration
     * @param mixed $products
     * @return mixed
     */
    protected function _attachScopeFilter($registration, $products)
    {
        switch ($registration->getScope()) {
            case 'website':
                $products->addWebsiteFilter($registration->getScopeId());
                break;
            case 'store':
                $products->addStoreFilter($registration->getScopeId());
                break;
            default:
                return $products;
        }
        return $products;
    }

    /**
     * @see parent
     */
    protected function _sendTest($registration, $data)
    {
        $products = [];
        if (array_key_exists('productSku', $data)) {
            $products = $this->_collection()->addFieldToFilter('sku', [
                'eq' => $data['productSku']
            ]);
            return $this->_attachScopeFilter($registration, $products);
        }
        return $products;
    }

    /**
     * @param string $name
     * @param string $id
     * @return string
     */
    private function generateOptionName($name, $id)
    {
        return $name . ' (' . $id . ')';
    }
}

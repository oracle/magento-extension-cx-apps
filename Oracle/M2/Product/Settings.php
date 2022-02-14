<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Product;

class Settings extends \Oracle\M2\Core\Config\ContainerAbstract implements SettingsInterface
{
    const EVENT_NAME = "oracle_product_extra_fields";
    const EVENT_DEFAULT_FIELDS = "oracle_product_default_fields";
    const EVENT_CUSTOM_FIELDS = "oracle_product_custom_fields";
    const EVENT_ATTRIBUTE_FIELDS = "oracle_product_attribute_fields";

    protected $_data;
    protected $_storeManager;
    protected $_eventManager;
    protected $_productRepo;
    protected $_caches = [];

    /**
     * @param \Oracle\M2\Core\Config\ScopedInterface $config
     * @param \Oracle\M2\Core\Config\FactoryInterface $data
     * @param \Oracle\M2\Core\Event\ManagerInterface $eventManager
     * @param \Oracle\M2\Core\Store\ManagerInterface $storeManager
     * @param \Oracle\M2\Core\Catalog\ProductCacheInterface $productRepo
     */
    public function __construct(
        \Oracle\M2\Core\Config\ScopedInterface $config,
        \Oracle\M2\Core\Config\FactoryInterface $data,
        \Oracle\M2\Core\Event\ManagerInterface $eventManager,
        \Oracle\M2\Core\Store\ManagerInterface $storeManager,
        \Oracle\M2\Core\Catalog\ProductCacheInterface $productRepo,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::__construct($config);
        $this->_data = $data;
        $this->_storeManager = $storeManager;
        $this->_eventManager = $eventManager;
        $this->_productRepo = $productRepo;
        $this->logger = $logger;
    }

    /**
     * @see parent
     */
    public function isEnabled($scope = 'default', $scopeId = null)
    {
        return $this->_config->isSetFlag(self::XML_PATH_ENABLED, $scope, $scopeId);

    }

    /**
     * @see parent
     */
    public function isProductAddLink($scope = 'default', $scopeId = null)
    {
        return $this->_config->isSetFlag(self::XML_PATH_ADD_LINK, $scope, $scopeId);
    }

    /**
     * @see parent
     */
    public function getDefaultFields(\Oracle\M2\Connector\RegistrationInterface $registration)
    {
        return $this->_dispatchFields(self::EVENT_DEFAULT_FIELDS, [
            'registration' => $registration
        ]);
    }

    /**
     * @see parent
     */
    public function getCustomFields(\Oracle\M2\Connector\RegistrationInterface $registration)
    {
        return $this->_dispatchFields(self::EVENT_CUSTOM_FIELDS, [
            'registration' => $registration
        ]);
    }

    /**
     * @see parent
     */
    public function getFieldAttributes(\Oracle\M2\Connector\RegistrationInterface $registration)
    {
        return $this->_dispatchFields(self::EVENT_ATTRIBUTE_FIELDS, [
            'registration' => $registration
        ]);
    }

    /**
     * @see parent
     */
    public function getEnabledStores($scope = 'default', $scopeId = null)
    {
        $stores = [];
        if ($scope != 'default') {
            $scope .= 's';
        }
        $data = $this->_data->getCollection()
            ->addFieldToFilter('path', ['like' => self::XML_PATH_SCOPES])
            ->addFieldToFilter('scope', ['eq' => $scope])
            ->addFieldToFilter('scope_id', ['eq' => $scopeId]);
        foreach ($data as $config) {
            if ($config->getValue() == 1) {
                $code = substr($config->getPath(), strrpos($config->getPath(), '/') + 1);
                $store = $this->_storeManager->getStore($code);
                if ($store) {
                    $stores[$code] = $store->getId();
                }
            }
        }
        return $stores;
    }

    /**
     * @see parent
     */
    public function getFieldMapping($product)
    {
        $data = [];
        $originalProduct = $product;
        // $mappings = $this->getAll($product->getStoreId());

        $mappings = array(
            "product_url" => "product_url",
            "qty" => "quantity",
            "color" => "color",
            "gender" => "gender",
            "image" => "image_url",
            "description" => "description",
            "review_cnt" => "review_count",
            "avg_rating" => "average_rating",
            "is_in_stock" => "availability",
            "name" => "title",
            "special_price" => "sale_price",
            "special_to_date" => "Sale_Price_Effective_End_Date",
            "parent_product_id" => "parent_product_id",
            "price" => "price",
            "sku" => "product_id",
            "product_category" => "product_category",
            "special_from_date" => "Sale_Price_Effective_Start_Date"
        );

        $data["product_id"] = $product->getId();

        $scopes = $product->getScopes();
        $product->getResource()->load($product, $product->getId(), array_keys($mappings));
        $extraFields = $this->_dispatchFields(self::EVENT_NAME, [
            'product' => $product,
            'mappings' => $mappings
        ]);
        foreach ($mappings as $code => $field) {
            $product = $originalProduct;
            $attribute = false;
            $value = null;
            if (array_key_exists($code, $extraFields)) {
                $value = $extraFields[$code];
            } else {
                $attribute = $product->getResource()->getAttribute($code);
                $value = $product->getData($code);
                if (is_array($value)) {
                	$value = implode(', ', $value);
                }
                if ($attribute && !$attribute->getIsGlobal() && !empty($scopes)) {
                    foreach ($scopes as $scope => $scopeId) {
                        $scopedField = "{$code}_{$scope}";
                        $product = $this->_productRepo->getById($originalProduct->getId(), $scopeId);
                        $scopedValue = $product->getData($code);
                        if ($product) {
                            $scopedValue = $this->_processAttributeValue($attribute, $scopedValue, $product);
                        }
                        $data[$scopedField] = $scopedValue;
                    }
                }
            }
            $data[$code] = $this->_processAttributeValue($attribute, $value, $product);
        }
        return $data;
    }

    /**
     * @see parent
     */
    public function getAll($storeId = null)
    {
        $store = $this->_storeManager->getStore($storeId);
        if (!array_key_exists($store->getId(), $this->_caches)) {
            $this->_caches[$store->getId()] = $this->_getAllDefaults($store) + $this->_getAllCustoms($store);
        }
        return $this->_caches[$store->getId()];
    }

    /**
     * Returns the converted type for the value / attribute
     *
     * @param mixed $attribute
     * @param mixed $value
     * @param mixed $product
     * @return mixed
     */
    protected function _processAttributeValue($attribute, $value, $product)
    {
        if (is_null($value)) {
            return '';
        }
        if (empty($attribute)) {
            return $value;
        }
        // Special case the image
        if (preg_match('/Image$/', get_class($attribute->getFrontend()))) {
            $value = $this->_productRepo->getImage($product, $attribute->getAttributeCode());
        }
        switch ($attribute->getFrontendInput()) {
            case 'select':
                return $attribute->getSource()->getOptionText($value);
            case 'multiselect':
                $values = [];
                if (!is_array($value)) {
                    $value = explode(',', $value);
                }
                if (!is_array($value)) {
                    $value = [$value];
                }
                $source = $attribute->getSource();
                foreach ($value as $val) {
                    $values[] = $source->getOptionText($val);
                }
                return implode(',', $values);
            case 'date':
            case 'datetime':
                return date('c', strtotime($value));
            default:
                return $value;
        }
    }

    /**
     * Gets a collection of fields used by the extension in decoupled
     *
     * @param string $eventName
     * @param array $eventArgs
     * @return array
     */
    protected function _dispatchFields($eventName, $eventArgs = [])
    {
        $container = new \Oracle\M2\Core\DataObject(['fields' => []]);
        $this->_eventManager->dispatch($eventName, $eventArgs + [
            'container' => $container
        ]);
        return $container->getFields();
    }

    /**
     * Scans teh DB for stored custom mappings
     *
     * @param mixed $store
     * @return array
     */
    protected function _getAllCustoms($store)
    {
        $specificity = [];
        $savedAttributes = [];
        $data = $this->_data
            ->getCollection()
            ->addPathFilter(self::XML_PATH_CUSTOMS);
        foreach ($data as $config) {
            $isValid = $this->_validScope($config, $store);
            if ($isValid && $this->_moreSpecific($config, $specificity)) {
                if (array_key_exists($config->getPath(), $specificity)) {
                    list($scope, $value) = $specificity[$config->getPath()];
                    unset($savedAttributes[$value]);
                }
                $mapping = unserialize($config->getValue());
                $code = $mapping['attribute'];
                $savedAttributes[$mapping['attribute']] = $code;
                $specificity[$config->getValue()] = [$config->getScope(), $mapping['attribute']];
            }
        }
        return $savedAttributes;
    }

    /**
     * Scans the DB for stored default mappings
     *
     * @param mixed $store
     * @return array
     */
    protected function _getAllDefaults($store)
    {
        $specificity = [];
        $savedAttributes = [];
        $data = $this->_data
            ->getCollection()
            ->addPathFilter(self::XML_PATH_DEFAULTS);
        foreach ($data as $config) {
            $isValid = $this->_validScope($config, $store);
            if ($isValid && $this->_moreSpecific($config, $specificity)) {
                if (array_key_exists($config->getPath(), $specificity)) {
                    list($scope, $value) = $specificity[$config->getPath()];
                    unset($savedAttributes[$value]);
                }
                $code = $config->getValue();
                $field = substr($config->getPath(), strrpos($config->getPath(), '/') + 1);
                $savedAttributes[$code] = $field;
                $specificity[$config->getPath()] = [$config->getScope(), $code];
            }
        }
        return $savedAttributes;
    }
}

<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Contact;

abstract class SettingsAbstract extends \Oracle\M2\Core\Config\ContainerAbstract implements SettingsInterface
{
    const EVENT_PREFIX = "oracle_contact_extra_";

    protected static $_attributeKeys = [
        'attributes',
        'shipping_address_attributes',
        'billing_address_attributes'
    ];

    protected $_data;
    protected $_eventManager;
    protected $_storeManager;
    protected $_groupCache;

    /**
     * @param \Oracle\M2\Core\Store\ManagerInterface $storeManager
     * @param \Oracle\M2\Core\Customer\GroupCacheInterface $groupCache
     * @param \Oracle\M2\Core\Config\FactoryInterface $data
     * @param \Oracle\M2\Core\Config\ScopedInterface $config
     * @param \Oracle\M2\Core\Event\ManagerInterface $eventManager
     */
    public function __construct(
        \Oracle\M2\Core\Store\ManagerInterface $storeManager,
        \Oracle\M2\Core\Customer\GroupCacheInterface $groupCache,
        \Oracle\M2\Core\Config\FactoryInterface $data,
        \Oracle\M2\Core\Config\ScopedInterface $config,
        \Oracle\M2\Core\Event\ManagerInterface $eventManager
    ) {
        parent::__construct($config);
        $this->_data = $data;
        $this->_eventManager = $eventManager;
        $this->_groupCache = $groupCache;
        $this->_storeManager = $storeManager;
    }

    /**
     * @see parent
     */
    public function isEnabled($scopeType = 'default', $scopeCode = null)
    {
         return $this->_config->isSetFlag(self::XML_PATH_ENABLED, $scopeType, $scopeCode);
    }

    /**
     * @see parent
     */
    public function isSkipEmpty($scopeType = 'default', $scopeCode = null)
    {
        return $this->_config->isSetFlag(self::XML_PATH_SKIP_EMPTY, $scopeType, $scopeCode);
    }

    /**
     * @see parent
     */
    public function getGuestOrderToggle($scopeType = 'default', $scopeCode = null)
    {
        return $this->_config->getValue(self::XML_PATH_GUEST_ORDER, $scopeType, $scopeCode);
    }

    /**
     * @see parent
     */
    public function getAttributeFilters()
    {
        $attributes = [];
        $attributes[] = [
            'increment_id',
            'updated_at',
            'store_id',
            'entity_id',
            'attribute_set_id',
            'entity_type_id',
            'password_hash',
            'default_billing',
            'default_shipping',
            'email',
            'confirmation',
            'reward_update_notification',
            'reward_warning_notification',
            'disable_auto_group_change'
        ];
        $addressFilters = [
            'region',
            'vat_id',
            'vat_is_valid',
            'vat_request_id',
            'vat_request_date',
            'vat_request_success',
        ];
        $attributes[] = $addressFilters;
        $attributes[] = $addressFilters;
        return array_combine(self::$_attributeKeys, $attributes);
    }

    private function buildNewAccountResetPasswordURL($object, $storeId) {
        return $this->_storeManager->getStore($storeId)->getBaseUrl().'customer/account/createPassword/?id='.$object->getId().'&token='.$object->getData('rp_token');
    }

    private function buildResetPasswordURL($object, $storeId) {
        return $this->_storeManager->getStore($storeId)->getBaseUrl().'customer/account/createPassword/?token='.$object->getData('rp_token');
    }

    /**
     * @see parent
     */
    public function getFieldsForModel($object, $storeId, $type = 'contact', $eventName = '')
    {
        $fields = [];

        $config_attributes = array("attributes" =>
               array("firstname" => "firstname", "gender" => "gender", "dob" => "dob", "created_at" => "created_at", "lastname" => "lastname"),
            "billing_address_attributes" =>
                array("firstname" => "billing_address_attributes/firstname" ,"city" => "billing_address_attributes/city" ,"street" => "billing_address_attributes/street"
            ,"postcode" => "billing_address_attributes/postcode", "region_id" => "billing_address_attributes/region_id",
                "company" => "billing_address_attributes/company", "country_id" => "billing_address_attributes/country_id", "lastname" => "billing_address_attributes/lastname"),
            "shipping_address_attributes" =>
                 array()
        );


        if ($type == 'contact') {
            $fields[] = [
                'fieldId' => 'entityId',
                'content' => $object->getEntityId()
            ];
            if($object->getPasswordHash() == null) {
                $fields[] = [
                    'fieldId' => 'newAccountResetPasswordURL',
                    'content' => $this->buildNewAccountResetPasswordURL($object, $storeId)
                ];
            } elseif($eventName == "resetPassword") {
                $fields[] = [
                    'fieldId' => 'resetPasswordURL',
                    'content' => $this->buildResetPasswordURL($object, $storeId)
                ];
            } elseif($eventName == "forgotPassword") {
                $fields[] = [
                    'fieldId' => 'forgotPasswordURL',
                    'content' => $this->buildResetPasswordURL($object, $storeId)
                ];
            }
        }

        $store = $this->_storeManager->getStore($storeId);
        $skipEmpty = $this->isSkipEmpty('store', $storeId);
        foreach ($this->getAttributesToModel($type, $storeId) as $key => $func) {
           // $attributes = $this->_savedAttributes($key, $store);
            $attributes = $config_attributes[$key];
            if (!empty($attributes)) {
                $container = new \Oracle\M2\Core\DataObject([
                    'extra' => [],
                    'attributes' => $attributes
                ]);
                // EVENT_PREFIX:- oracle_contact_extra_
                $this->_eventManager->dispatch(self::EVENT_PREFIX . $type . '_'. $key, [
                    $type => $object,
                    'storeId' => $storeId,
                    'container' => $container
                ]);
                $extra = $container->getExtra();
                $dataModel = call_user_func($func, $object, array_keys($attributes));
                if ($dataModel) {
                    foreach ($attributes as $code => $oracleId) {
                        $skipAttrCheck = array_key_exists($code, $extra);
                        if ($skipAttrCheck) {
                            $value = $extra[$code];
                        } else {
                            $value = $dataModel->getData($code);
                        }
                        if ($skipEmpty && (is_null($value) || $value === '')) {
                            continue;
                        }
                        $fields[] = [
                            'fieldId' => $oracleId,
                            'content' => $this->_attributeData($value, $dataModel, $code, $skipAttrCheck)
                        ];
                    }
                }
            }
        }
        return $fields;
    }

    /**
     * @see parent
     */
    public function getAttributeDisplayType($attribute)
    {
        switch ($attribute->getFrontendInput()) {
            case 'obscure':
            case 'password':
                return 'password';
            case 'radio':
            case 'select':
                return 'select';
            case 'boolean':
                return 'checkbox';
            case 'int':
            case 'integer':
            case 'numeric':
                return 'integer';
            case 'decimal':
            case 'price':
                return 'float';
            case 'date':
            case 'datetime':
                return 'date';
            case 'textarea':
                return 'textarea';
            default:
                return 'text';
        }
    }

    /**
     * Gets the model with the corresponding loader function
     *
     * @param string $contact
     * @param mixed $storeId
     * @return array
     */
    public function getAttributesToModel($type = 'contact', $storeId = null)
    {
        $attributes = array_combine(self::$_attributeKeys, [
            [$this, $type . 'ModelLoader'],
            [$this, $type . 'ShippingModelLoader'],
            [$this, $type . 'BillingModelLoader']
        ]);
        if ($type == 'order') {
            switch ($this->getGuestOrderToggle('store', $storeId)) {
                case 'shipping':
                    unset($attributes['billing_address_attributes']);
                    break;
                case 'billing':
                    unset($attributes['shipping_address_attributes']);
                    break;
                case 'none':
                    $attributes = [];
            }
        }
        return $attributes;
    }

    /**
     * Pre-loads the customer attributes from an array of codes
     *
     * @param \Magento\Customer\Model\Customer $customer
     * @param array $attributes
     * @return \Magento\Customer\Model\Customer
     */
    public function contactModelLoader($customer, $attributes)
    {
        $resource = $customer->getResource();
        $resource->load($customer, $customer->getId(), array_merge($attributes, [
            'default_shipping', 'default_billing'
        ]));
        return $customer;
    }

    /**
     * Pre-loads a plain object with customer attributes from an order
     *
     * @param \Magento\Sales\Model\Order $order
     * @param array $attributes
     * @return \Oracle\M2\Core\DataObject
     */
    public function orderModelLoader($order, $attributes)
    {
        $data = ['email' => $order->getCustomerEmail(), 'store_id' => $order->getStoreId()];
        foreach ($attributes as $attributeCode) {
            $data[$attributeCode] = $order->getData('customer_' . $attributeCode);
        }
        return new \Oracle\M2\Core\DataObject($data + ['resource' => false]);
    }

    /**
     * Gets primary billing address for the customer
     *
     * @param \Magento\Customer\Model\Customer $customer
     * @param array $attributes
     * @return \Magento\Customer\Model\Resource\Address
     */
    public function contactBillingModelLoader($customer, $attributes)
    {
        $billing = $customer->getPrimaryBillingAddress();
        if (!$billing) {
            return $this->contactShippingModelLoader($customer, $attributes);
        }
        return $billing;
    }

    /**
     * Gets the billing address associated with an order
     *
     * @param \Magento\Sales\Model\Order $order
     * @param array $attributes
     * @return mixed
     */
    public function orderBillingModelLoader($order, $attributes)
    {
        return $order->getBillingAddress();
    }

    /**
     * Gets primary shipping address for the customer
     *
     * @param \Magento\Customer\Model\Customer $customer
     * @param array $attributes
     * @return \Magento\Customer\Model\Resource\Address
     */
    public function contactShippingModelLoader($customer, $attributes)
    {
        $shipping = $customer->getPrimaryShippingAddress();
        if (!$shipping) {
            foreach ($customer->getAdditionalAddresses() as $shipping) {
                break;
            }
        }
        return $shipping;
    }

    /**
     * Gets the billing address associated with an order
     *
     * @param \Magento\Sales\Model\Order $order
     * @param array $attributes
     * @return mixed
     */
    public function orderShippingModelLoader($order, $attributes)
    {
        return $order->getShippingAddress();
    }

    /**
     * Gets the saved attributes for the attribute suffix and store
     *
     * @param string $attrkey
     * @param mixed $store
     * @return array
     */
    protected function _savedAttributes($attrkey, $store)
    {
        $path = 'oracle/contact/extensions/' . $attrkey;
        $data = $this->_data->getCollection()->addPathFilter($path);
        $specificity = [];
        $savedAttributes = [];
        foreach ($data as $config) {
            if ($this->_validScope($config, $store) && $this->_moreSpecific($config, $specificity)) {
                if (array_key_exists($config->getPath(), $specificity)) {
                    list($scope, $value) = $specificity[$config->getPath()];
                    unset($savedAttributes[$value]);
                }
                $parts = explode('/', $config->getPath());
                $code = end($parts);
                $savedAttributes[$code] = $config->getValue();
                $specificity[$config->getPath()] = [$config->getScope(), $code];
            }
        }
        return $savedAttributes;
    }

    /**
     * Gets the attribute value for a data model and code
     *
     * @param mixed $value
     * @param mixed $dataModel
     * @param string $attributeCode
     * @param boolean $skipAttrCheck
     * @return string
     */
    protected function _attributeData($value, $dataModel, $attributeCode, $skipAttrCheck = false)
    {
        if ($attributeCode == 'region_id') {
            return $dataModel->getRegionCode();
        } elseif ($attributeCode == 'country_id') {
            return $dataModel->getCountryId();
        } elseif ($attributeCode == 'group_id') {
            $group = $this->_groupCache->getById($value);
            return !$group ? '' : $group->getCode();
        } elseif ($attributeCode == 'website_id') {
            $website = $this->_storeManager->getWebsite($value);
            return !$website ? '' : $website->getName();
        } elseif ($attributeCode == 'store_id') {
            $store = $this->_storeManager->getStore($value);
            return !$store ? '' : $store->getName();
        }
        if (!$skipAttrCheck && method_exists($dataModel->getResource(), 'getAttribute')) {
            $attribute = $dataModel->getResource()->getAttribute($attributeCode);
            switch ($attribute->getFrontendInput()) {
                case 'select':
                    $value = $attribute->getSource()->getOptionText($value);
                    if ($value === false) {
                        return '';
                    }
                    return $value;
                case 'date':
                case 'datetime':
                    if (empty($value)) {
                        return '';
                    }
                    return $this->_formatDate($value);
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
                default:
                    return $value;
            }
        } else {
            // Exists in the flat order case
            switch ($attributeCode) {
                case 'dob':
                    if (empty($value)) {
                        return '';
                    }
                    return $this->_formatDate($value);
                default:
                    return $value;
            }
        }
    }

    /**
     * Formats a date like value
     *
     * @param string $value
     * @return string
     */
    protected function _formatDate($value)
    {
        return date('c', strtotime($value));
    }
}

<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Contact;

use Magento\Sales\Model\ResourceModel\Order\Collection as OrderCollection;
use Magento\Customer\Model\ResourceModel\Customer\Collection as CustomerCollection;

abstract class ExtensionAbstract extends \Oracle\M2\Connector\Discovery\AdvancedExtensionAbstract implements \Oracle\M2\Connector\Discovery\GroupInterface, \Oracle\M2\Connector\Discovery\TransformEventInterface
{
    /** @var \Oracle\M2\Core\Sales\OrderCacheInterface */
    protected $_orderRepo;

    /** @var \Oracle\M2\Core\Customer\CacheInterface */
    protected $_customerRepo;

    /** @var \Oracle\M2\Connector\Event\PushLogic */
    protected $_orderPush;

    /** @var \Oracle\M2\Contact\Event\GuestFromOrder */
    protected $_orderSource;

    /** @var bool */
    protected $_useOrderSource = false;

    /**
     * @param \Oracle\M2\Core\Sales\OrderCacheInterface $orderRepo
     * @param \Oracle\M2\Core\Customer\CacheInterface $customerRepo
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
        \Oracle\M2\Core\Sales\OrderCacheInterface $orderRepo,
        \Oracle\M2\Core\Customer\CacheInterface $customerRepo,
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
        $this->_orderRepo = $orderRepo;
        $this->_customerRepo = $customerRepo;
        $this->_orderSource = new \Oracle\M2\Contact\Event\GuestFromOrder($this->_helper);
        $this->_orderPush = new \Oracle\M2\Connector\Event\PushLogic(
            $queueManager,
            $connectorSettings,
            $helper,
            $platform,
            $this->_orderSource,
            $this->_orderSource
        );
    }

    /**
     * @see parent
     */
    public function getSortOrder()
    {
        return 5;
    }

    /**
     * @see parent
     */
    public function getEndpointId()
    {
        return 'contact';
    }

    /**
     * @see parent
     */
    public function getEndpointName()
    {
        return 'Contacts';
    }

    /**
     * @see parent
     */
    public function getEndpointIcon()
    {
        return 'mage-icon-contacts';
    }

    /**
     * @see parent
     */
    public function gatherEndpoints($observer)
    {
        $observer->getDiscovery()->addGroupHelper($this);
    }

    /**
     * Updates a guest in Oracle, if the customer was a guest
     * @return void
     */
    public function updateGuestInfo($observer)
    {
        $order = $observer->getOrder();
        if ($this->_helper->getGuestOrderToggle('store', $order->getStoreId()) != 'none') {
            $this->_orderPush->pushEvent($order, $order->getStoreId());
        }
    }

    /**
     * @see parent
     */
    public function advancedAdditional($observer)
    {
        $observer->getEndpoint()->addOptionToScript("test", "jobName", [
            'id' => 'test_' . $this->getEndpointId(),
            'name' => 'Contact'
        ]);

        $observer->getEndpoint()->addFieldToScript('test', [
            'id' => 'customerEmail',
            'name' => 'Customer Email',
            'type' => 'text',
            'position' => 5,
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

        $observer->getEndpoint()->addOptionToScript('event', 'moduleSettings', [
            'id' => $this->getEndpointId(),
            'name' => $this->getEndpointName()
        ]);

        $observer->getEndpoint()->addFieldToScript('historical', [
            'id' => 'source',
            'name' => 'Source',
            'type' => 'select',
            'position' => 3,
            'typeProperties' => [
                'options' => [
                    [
                        'id' => 'customer',
                        'name' => 'Registered Users'
                    ],
                    [
                        'id' => 'order',
                        'name' => 'Non-Registered Users (via Guest Orders)'
                    ]
                ],
                'default' => 'customer'
            ],
            'depends' => [
                [ 'id' => 'jobName', 'values' => [ $this->getEndpointId() ] ]
            ]
        ]);
    }

    /**
     * @see parent
     */
    public function endpointInfo($observer)
    {
        /** @var \Oracle\M2\Connector\Discovery\Endpoint $endpoint */
        $endpoint = $observer->getEndpoint();
        $endpoint->addExtension([
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
                    'id' => 'skipEmpty',
                    'name' => 'Skip Empty Values',
                    'type' => 'boolean',
                    'required' => true,
                    'typeProperties' => [ 'default' => false ]
                ],
                [
                    'id' => 'guestOrder',
                    'name' => 'Sync Guest Order Fields',
                    'type' => 'select',
                    'required' => true,
                    'typeProperties' => [
                        'default' => 'merge',
                        'options' => [
                            [ 'id' => 'shipping', 'name' => 'Shipping Address Only'],
                            [ 'id' => 'billing', 'name' => 'Billing Address Only'],
                            [ 'id' => 'merge', 'name' => 'Both Shipping and Billing Address'],
                            [ 'id' => 'none',  'name' => 'Do not Sync Contact Fields']
                        ]
                    ]
                ]
            ]
        ]);
        $attributes = $this->_helper->getAttributes();
        $attributeLabels = $this->_helper->getAttributeLabels();
        $attributeFilters = $this->_helper->getAttributeFilters();
        foreach ($attributes as $attributeId => $attributeSet) {
            $attributeFields = [];
            foreach ($attributeSet as $attributeField) {
                if ($this->_shouldSkip($attributeField, $attributeFilters[$attributeId])) {
                    continue;
                }
                $attributeFields[] = [
                    'id' => $attributeField->getAttributeCode(),
                    'name' => $attributeField->getFrontendLabel(),
                    'type' => 'select',
                    'typeProperties' => [
                        'oracle' => [
                            'type' => 'contactField',
                            'displayType' => $this->_helper->getAttributeDisplayType($attributeField)
                        ]
                    ]
                ];
            }
            $endpoint->addExtension([
                'id' => $attributeId,
                'name' => $attributeLabels[$attributeId],
                'fields' => $attributeFields
            ]);
        }

        $endpoint->addAutoConfigData(
            $this->getEndpointId(),
            $observer->getRegistration()->getScopeHash(),
            $this->getAutoConfigData('contact')
        );
    }

    /**
     * @see parent
     */
    public function transformEvent($observer)
    {
        $data = [];
        $transform = $observer->getTransform();
        $event = $transform->getContext();
        if (array_key_exists('type', $event) && $event['type'] == 'order') {
            $order = $this->_orderRepo->getById($event['id']);
            if ($order) {
                $data = $this->_orderSource->transform($order);
            }
        } else {
            $customer = $this->_customerRepo->getById($event['id']);
            if ($customer) {
                if($event['resetPassword']) {
                    $data = $this->_source->transformForEvent($customer, "resetPassword");
                } elseif ($event['forgotPassword']) {
                    $data = $this->_source->transformForEvent($customer, "forgotPassword");
                } else {
                    $data = $this->_source->transform($customer);
                }
            }
        }
        $transform->setContact($data);
    }

    /**
     * @see parent
     */
    protected function _getObject($observer)
    {
        return $observer->getCustomer();
    }

    /**
     * @see \Oracle\M2\Connector\Discovery\ExtensionPushEventAbstract::pushChanges
     */
    public function pushChanges($observer)
    {
        if ($observer->getAccountController()) {
            try {
                $customer = $this->_customerRepo->getById($this->_getObject($observer)->getId());
                // Note: making it to run in the foreground, instead of real time.
                $this->_pushLogic->pushEvent($customer, $customer->getStoreId(), true);
            } catch (\Exception $e) {
            }
        } else {
            parent::pushChanges($observer);
        }
    }

    /**
     * Determines email change request will success and update
     *
     * @param string $customerId
     * @param string $email
     * @return void
     */
    public function updateEmail($customerId, $email)
    {
        $customer = $this->_customerRepo->getById($customerId);
        if ($customer && $customer->getEmail() != $email) {
            $customer = clone $customer;
            $emailCustomer = $this->_customerRepo->getByEmail($email);
            if (empty($emailCustomer) && \Zend_Validate::is($email, 'EmailAddress')) {
                $customer->setEmail($email)->setIsUpdateEmail(true);
                $this->_pushLogic->pushEvent($customer, $this->_storeManager->getStore()->getId(), true);
            }
        }
    }

    /**
     * @see parent
     */
    protected function _historicalAction($data, $object)
    {
        return 'add';
    }

    /**
     * @see parent
     */
    protected function _source($data)
    {
        return $this->_useOrderSource ? $this->_orderSource : $this->_source;
    }

    /**
     * @see parent
     */
    protected function _sendTest($registration, $data)
    {
        $customers = [];
        if (array_key_exists('customerEmail', $data)) {
            $customers = $this->_attachCustomerScopeFilter($data, $this->_contactCollection());
            $customers->addFieldToFilter('email', ['eq' => $data['customerEmail']]);
            if ($customers->getSize()) {
                return $customers;
            }
            $this->_useOrderSource = true;
            $customers = $this->_attachOrderScopeFilter($data, $this->_orderCollection())
                ->addFieldToFilter('customer_email', ['eq' => $data['customerEmail']])
                ->addFieldToFilter('customer_is_guest', ['eq' => 1]);
            $customers->getSelect()->group('customer_email');
        }
        return $customers;
    }

    /**
     * @see parent
     * @return OrderCollection|CustomerCollection|[]
     */
    protected function _sendHistorical($registration, $data)
    {
        $collection = $this->_attachScopeFilter($data['options']);
        if (array_key_exists('startTime', $data)) {
            $startTime = $data['startTime'];
            if ($startTime) {
                $collection->addFieldToFilter('updated_at', ['gt' => $startTime]);
            }
        }
        if (array_key_exists('endTime', $data)) {
            $endTime = $data['endTime'];
            if ($endTime) {
                $collection->addFieldToFilter('updated_at', ['lt' => $endTime]);
            }
        }
        return $collection;
    }

    /**
     * Attributes lacking a frontend label or found in the filters are skipped
     *
     * @param mixed $attribute
     * @param array $filters
     * @return boolean
     */
    protected function _shouldSkip($attribute, $filters)
    {
        return (
            $attribute->getFrontendLabel() == '' ||
            in_array($attribute->getAttributeCode(), $filters)
        );
    }

    /**
     * Adds scope awareness to a generated collection
     *
     * @param array $data
     * @return OrderCollection|CustomerCollection|[]
     */
    protected function _attachScopeFilter($data)
    {
        $collection = [];
        $emailField = 'email';
        if ($data['source'] == 'order') {
            $this->_useOrderSource = true;
            $collection = $this->_attachOrderScopeFilter($data, $this->_orderCollection())
                ->addFieldToFilter('customer_is_guest', ['eq' => 1]);
            $collection->getSelect()->group('customer_email');
        } else {
            $collection = $this->_attachCustomerScopeFilter($data, $this->_contactCollection());
        }
        return $collection;
    }

    /**
     * Added scope awareness to the collection
     *
     * @param array $data
     * @param mixed $customers
     * @return mixed
     */
    protected function _attachCustomerScopeFilter($data, $customers)
    {
        list($scopeName, $scopeId) = explode('.', $data['scopeId']);
        switch ($scopeName) {
            case 'website':
                return $customers->addFieldToFilter('website_id', ['eq' => $scopeId]);
            case 'store':
                return $customers->addFieldToFilter('store_id', ['eq' => $scopeId]);
        }
        return $customers;
    }

    /**
     * Add scopes awareness to order collections
     *
     * @param array $data
     * @param mixed $orders
     * @return mixed
     */
    protected function _attachOrderScopeFilter($data, $orders)
    {
        list($scopeName, $scopeId) = explode('.', $data['scopeId']);
        switch ($scopeName) {
            case 'website':
                $storeIds = [];
                $website = $this->_storeManager->getWebsite($scopeId);
                foreach ($website->getStores() as $store) {
                    $storeIds[] = $store->getId();
                }
                return $orders->addFieldToFilter('store_id', ['in' => $storeIds]);
            case 'store':
                return $orders->addFieldToFilter('store_id', ['eq' => $scopeId]);
        }
        return $orders;
    }

    /**
     * Implementors would provide a mutable collection
     *
     * @return CustomerCollection
     */
    abstract protected function _contactCollection();

    /**
     * Implementors would provide a mutable order collection
     *
     * @return OrderCollection
     */
    abstract protected function _orderCollection();
}

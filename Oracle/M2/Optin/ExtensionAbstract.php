<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Optin;

abstract class ExtensionAbstract extends \Oracle\M2\Connector\Discovery\AdvancedExtensionAbstract implements \Oracle\M2\Connector\Discovery\GroupInterface, \Oracle\M2\Connector\Discovery\TransformEventInterface
{
    /** @var \Oracle\M2\Core\Subscriber\ManagerInterface */
    protected $_subscribers;

    /**
     * @param \Oracle\M2\Core\Subscriber\ManagerInterface $subscribers
     * @param \Oracle\M2\Core\App\EmulationInterface $appEmulation
     * @param \Oracle\M2\Core\Store\ManagerInterface $storeManager
     * @param \Oracle\M2\Connector\QueueManagerInterface $queueManager
     * @param \Oracle\M2\Connector\SettingsInterface $connectorSettings
     * @param SettingsInterface $helper
     * @param \Oracle\M2\Connector\Event\PlatformInterface $platform
     * @param \Oracle\M2\Connector\Event\SourceInterface $source
     * @param \Magento\Framework\Filesystem\DriverInterface $fileSystemDriver
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Oracle\M2\Core\Subscriber\ManagerInterface $subscribers,
        \Oracle\M2\Core\App\EmulationInterface $appEmulation,
        \Oracle\M2\Core\Store\ManagerInterface $storeManager,
        \Oracle\M2\Connector\QueueManagerInterface $queueManager,
        \Oracle\M2\Connector\SettingsInterface $connectorSettings,
        SettingsInterface $helper,
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
        $this->_subscribers = $subscribers;
    }

    /**
     * Platforms will implement this to subscribe someone after
     * a purchase (potentially)
     *
     * @param mixed $observer
     */
    abstract public function subscribeAfterCheckout($observer);

    /**
     * @see parent
     */
    public function getSortOrder()
    {
        return 10;
    }

    /**
     * @see parent
     */
    public function getEndpointId()
    {
        return 'optin';
    }

    /**
     * @see parent
     */
    public function getEndpointName()
    {
        return 'Opt-Ins';
    }

    /**
     * @see parent
     */
    public function getEndpointIcon()
    {
        return 'mage-icon-optins';
    }

    /**
     * A script url is invoked by the Middleware to sync
     * a collection of emails and potentially unsubscribe them
     * in Magento
     *
     * @param mixed $observer
     */
    public function syncSubscribes($observer)
    {
        $script = $observer->getScript()->getObject();
        $registration = $observer->getScript()->getRegistration();
        $scopeName = $registration->getScope();
        $scopeId = $registration->getScopeId();
        $websiteId = '';
        switch ($scopeName) {
            case 'website':
                $websiteId = $scopeId;
                break;
            case 'store':
                $websiteId = $this->_storeManager->getStore($scopeId)->getWebsiteId();
                break;
        }
        $results = [
            'success' => 0,
            'error' => 0,
            'skipped' => 0
        ];
        $emails = $script['data']['emails'];
        foreach ($emails as $email) {
           // $subscriber = $this->_subscribers->getByEmail($email);
            // Note:- getByEmail method has been deprecated.
            $subscriber = $this->_subscribers->getBySubscriberEmail($email, $websiteId);

            if (empty($subscriber)) {
                $results['skipped']++;
                continue;
            }
            if (!$this->_helper->isEnabled('store', $subscriber->getStoreId()) || !$this->_helper->isSyncUnsub('store', $subscriber->getStoreId())) {
                $results['skipped']++;
                continue;
            }
            if ($this->_subscribers->unsubscribe($email, false, 'oracle')) {
                $results['success']++;
            } else {
                $results['error']++;
            }
        }
        $observer->getScript()->setProgress($results);
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
    public function advancedAdditional($observer)
    {
        $observer->getEndpoint()->addOptionToScript('historical', 'jobName', [
            'id' => $this->getEndpointId(),
            'name' => $this->getEndpointName()
        ]);

        $observer->getEndpoint()->addFieldToScript('historical', [
            'id' => 'setStatus',
            'name' => 'Set Status In Oracle',
            'type' => 'select',
            'position' => 5,
            'typeProperties' => [
                'default' => 'new',
                'options' => [
                    [ 'id' => 'new', 'name' => 'Only New Contacts' ],
                    [ 'id' => 'any', 'name' => 'Any Contact' ]
                ]
            ],
            'depends' => [
                [ 'id' => 'jobName', 'values' => [ $this->getEndpointId() ] ]
            ]
        ]);

        $observer->getEndpoint()->addFieldToScript('historical', [
            'id' => 'subscriberSource',
            'name' => 'Source',
            'type' => 'select',
            'position' => 6,
            'typeProperties' => [
                'default' => 'all',
                'options' => [
                    [ 'id' => 'all', 'name' => 'Registerd and Guest Subscribers' ],
                    [ 'id' => 'gt', 'name' => 'Only Registered Subscribers' ],
                    [ 'id' => 'eq', 'name' => 'Only Guest Subscribers' ]
                ]
            ],
            'depends' => [
                [ 'id' => 'jobName', 'values' => [ $this->getEndpointId() ] ]
            ]
        ]);

        $observer->getEndpoint()->addFieldToScript('historical', [
            'id' => 'subscriberStatus',
            'name' => 'Status Filter',
            'type' => 'select',
            'position' => 7,
            'typeProperties' => [
                'default' => '1',
                'options' => [
                    [ 'id' => 'all', 'name' => 'All Newsletter Subscribers' ],
                    [ 'id' => '1', 'name' => 'Only Subscribed' ],
                    [ 'id' => '3', 'name' => 'Only Unsubscribed' ],
                    [ 'id' => '4', 'name' => 'Only Unconfirmed' ]
                ]
            ],
            'depends' => [
                [ 'id' => 'jobName', 'values' => [ $this->getEndpointId() ] ]
            ]
        ]);

        $observer->getEndpoint()->addOptionToScript('test', 'jobName', [
            'id' => 'test_' . $this->getEndpointId(),
            'name' => 'Opt-In'
        ]);

        $observer->getEndpoint()->addFieldToScript('test', [
            'id' => 'subscriberEmail',
            'name' => 'Subscriber Email',
            'type' => 'text',
            'position' => 7,
            'depends' => [
                [ 'id' => 'jobName', 'values' => [ 'test_' . $this->getEndpointId() ] ]
            ]
        ]);

        $observer->getEndpoint()->addOptionToScript('event', 'moduleSettings', [
            'id' => $this->getEndpointId(),
            'name' => $this->getEndpointName()
        ]);
    }

    /**
     * @see parent
     */
    public function endpointInfo($observer)
    {
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
                    'id' => 'syncUnsubs',
                    'name' => 'Sync Oracle Unsubscribes',
                    'type' => 'boolean',
                    'required' => true,
                    'typeProperties' => [ 'default' => true ]
                ],
                [
                    'id' => 'lists',
                    'name' => 'Add Opt-Ins to Lists',
                    'type' => 'select',
                    'typeProperties' => [
                        'oracle' => [ 'type' => 'list' ],
                        'multiple' => true
                    ]
                ],
                [
                    'id' => 'removeLists',
                    'name' => 'Remove Opt-Outs from Lists',
                    'type' => 'select',
                    'typeProperties' => [
                        'oracle' => [ 'type' => 'list' ],
                        'multiple' => true
                    ]
                ]
            ]
        ]);

        $observer->getEndpoint()->addExtension([
            'id' => 'checkout',
            'name' => 'Checkout',
            'fields' => [
                [
                    'id' => 'enabled',
                    'name' => 'Enabled',
                    'type' => 'boolean',
                    'required' => true,
                    'typeProperties' => [ 'default' => false ]
                ],
                [
                    'id' => 'source',
                    'name' => 'Add Opt-Ins to Source List',
                    'type' => 'select',
                    'depends' => [
                        [ 'id' => 'enabled', 'values' => [ true ] ]
                    ],
                    'typeProperties' => [
                        'oracle' => [ 'type' => 'list' ]
                    ]
                ],
                [
                    'id' => 'label',
                    'name' => 'Checkbox Label',
                    'type' => 'text',
                    'required' => true,
                    'typeProperties' => [
                        'default' => 'Sign Up for our Newsletter',
                    ],
                    'depends' => [
                        ['id' => 'enabled', 'values' => [ true ] ]
                    ]
                ],
                [
                    'id' => 'layout',
                    'name' => 'Checkbox Location',
                    'type' => 'select',
                    'required' => true,
                    'depends' => [
                        ['id' => 'enabled', 'values' => [ true ] ]
                    ],
                    'typeProperties' => [
                        'default' => 'shipping',
                        'options' => $this->_checkoutLayouts()
                    ]
                ],
                [
                    'id' => 'checked',
                    'name' => 'Checked by Default',
                    'type' => 'boolean',
                    'required' => true,
                    'depends' => [
                        ['id' => 'enabled', 'values' => [ true ] ]
                    ],
                    'typeProperties' => [ 'default' => true ]
                ],
            ]
        ]);

        $observer->getEndpoint()->addExtension([
            'id' => 'form',
            'name' => 'Embedded Webform',
            'fields' => [
                [
                    'id' => 'enabled',
                    'name' => 'Enabled',
                    'type' => 'boolean',
                    'required' => true,
                    'typeProperties' => [ 'default' => false ]
                ],
                [
                    'id' => 'secret',
                    'name' => 'Shared Secret',
                    'type' => 'text',
                    'required' => true,
                    'depends' => [
                        [
                            'id' => 'enabled',
                            'values' => [ true ]
                        ]
                    ]
                ],
                [
                    'id' => 'subscriberUrl',
                    'name' => 'Subscriber Lookup',
                    'type' => 'text',
                    'required' => true,
                    'depends' => [
                        [
                            'id' => 'enabled',
                            'values' => [ true ]
                        ]
                    ]
                ],
                [
                    'id' => 'height',
                    'name' => 'Form Height',
                    'type' => 'integer',
                    'typeProperties' => [
                        'min' => 100,
                        'default' => 700
                    ],
                    'depends' => [
                        [
                            'id' => 'enabled',
                            'values' => [ true ]
                        ]
                    ]
                ]
            ]
        ]);

        $observer->getEndpoint()->addAutoConfigData(
            $this->getEndpointId(),
            $observer->getRegistration()->getScopeHash(),
            $this->getAutoConfigData('optin')
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
        $subscriber = $this->_subscribers->getById($event['id']);
        if ($subscriber) {
            $data = $this->_source->transform($subscriber
                ->setIgnoreStatus($event['ignore_status'])
                ->setLocation($event['location']));
        }
        $transform->setSubscriber($data);
    }

    /**
     * Attaches the registered scope filter based on registration
     *
     * @param array $data
     * @param mixed $subscribers
     * @return mixed
     */
    protected function _attachScopeFilter($data, $subscribers)
    {
        list($scopeName, $scopeId) = explode('.', $data['scopeId']);
        switch ($scopeName) {
            case 'website':
                $storeIds = [];
                $website = $this->_storeManager->getWebsite($scopeId);
                foreach ($website->getStores() as $store) {
                    $storeIds[] = $store->getId();
                }
                return $subscribers->addFieldToFilter('store_id', [ 'in' => $storeIds ]);
            case 'store':
                return $subscribers->addFieldToFilter('store_id', [ 'eq' => $scopeId ]);
        }
        return $subscribers;
    }

    /**
     * @see parent
     */
    protected function _sendTest($registration, $data)
    {
        $subscribers = [];
        if (array_key_exists('subscriberEmail', $data)) {
            $subscribers = $this->_attachScopeFilter($data, $this->_subscribers->getCollection());
            $subscribers->addFieldToFilter('subscriber_email', [ 'eq' => $data['subscriberEmail'] ]);
        }
        return $subscribers;
    }

    /**
     * @see parent
     * @return \Magento\Newsletter\Model\ResourceModel\Subscriber\Collection
     */
    protected function _sendHistorical($registration, $data)
    {
        /** @var \Magento\Newsletter\Model\ResourceModel\Subscriber\Collection $subscribers */
        $subscribers = $this->_attachScopeFilter($data['options'], $this->_subscribers->getCollection());
        if (array_key_exists('subscriberSource', $data['options']) && $data['options']['subscriberSource'] != 'all') {
            $subscribers->addFieldToFilter('customer_id', [ $data['options']['subscriberSource'] => '0' ]);
        }
        if (array_key_exists('subscriberStatus', $data['options']) && $data['options']['subscriberStatus'] != 'all') {
            $subscribers->addFieldToFilter('subscriber_status', [ 'eq' => $data['options']['subscriberStatus'] ]);
        }
        if (array_key_exists('startTime', $data)) {
            $startTime = $data['startTime'];
            if ($startTime) {
                $subscribers->addFieldToFilter('change_status_at', ['gt' => $startTime]);
            }
        }
        if (array_key_exists('endTime', $data)) {
            $endTime = $data['endTime'];
            if ($endTime) {
                $subscribers->addFieldToFilter('change_status_at', ['lt' => $endTime]);
            }
        }

        return $subscribers;
    }

    /**
     * @see parent
     */
    protected function _historicalAction($data, $object)
    {
        $action = parent::_historicalAction($data, $object);
        if (array_key_exists('options', $data)) {
            if (array_key_exists('setStatus', $data['options']) && $data['options']['setStatus'] == 'new') {
                $action = 'add';
            }
        }
        return $action;
    }

    /**
     * Returns the default checkout layout tags
     * Implementations can override this functionality
     *
     * @return array
     */
    protected function _checkoutLayouts()
    {
        return [
            [ 'id' => 'shipping', 'name' => 'Shipping Step' ],
            [ 'id' => 'billing', 'name' => 'Billing Step' ],
            [ 'id' => 'review', 'name' => 'Review Step' ],
            [ 'id' => 'custom', 'name' => 'Custom Location' ]
        ];
    }

    /**
     * Internal method to be called once the checkout context is known
     *
     * @param mixed $storeId
     * @param string $email
     * @param boolean $optin
     */
    protected function _subscribeAfterCheckout($storeId, $email, $optin)
    {
        if ($this->_helper->isCheckoutEnabled('store', $storeId)) {
            if ($optin) {
                $subscriber = $this->_subscribers->getByEmail($email);
                if (empty($subscriber) || $subscriber->getSubscriberStatus() != 1) {
                    $this->_subscribers->subscribe($email, false, 'checkout');
                }
            }
        }
    }
}

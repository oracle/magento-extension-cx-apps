<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Email;

use Oracle\Email\Model\Trigger;

abstract class ExtensionAbstract extends \Oracle\M2\Connector\Discovery\AdvancedExtensionAbstract implements \Oracle\M2\Connector\Discovery\GroupInterface, \Oracle\M2\Connector\Discovery\TransformEventInterface
{
    /** @var \Oracle\M2\Email\TriggerManagerInterface */
    protected $_triggerManager;

    /** @var \Oracle\M2\Core\Event\ManagerInterface */
    protected $_eventManager;

    /** @var \Oracle\M2\Core\App\EmulationInterface */
    protected $_appEmulation;

    /** @var \Oracle\M2\Connector\MiddlewareInterface */
    protected $_middleware;

    /** @var \Oracle\M2\Core\Sales\OrderStatusesInterface */
    protected $_statuses;
    
    /** @var \Oracle\M2\Helper\Data  */
    protected $mageHelper;

    /**
     * @param TriggerManagerInterface $triggerManager
     * @param \Oracle\M2\Core\Sales\OrderStatusesInterface $statuses
     * @param \Oracle\M2\Core\Store\ManagerInterface $storeManager
     * @param \Oracle\M2\Connector\QueueManagerInterface $queueManager
     * @param \Oracle\M2\Connector\SettingsInterface $connectorSettings
     * @param \Oracle\M2\Connector\Event\HelperInterface $helper
     * @param \Oracle\M2\Connector\Event\PlatformInterface $platform
     * @param \Oracle\M2\Connector\Event\SourceInterface $source
     * @param \Oracle\M2\Core\Event\ManagerInterface $eventManager
     * @param \Oracle\M2\Helper\Data $mageHelper
     * @param \Magento\Framework\Filesystem\DriverInterface $fileSystemDriver
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        TriggerManagerInterface $triggerManager,
        \Oracle\M2\Core\Sales\OrderStatusesInterface $statuses,
        \Oracle\M2\Core\Store\ManagerInterface $storeManager,
        \Oracle\M2\Connector\QueueManagerInterface $queueManager,
        \Oracle\M2\Connector\SettingsInterface $connectorSettings,
        \Oracle\M2\Connector\Event\HelperInterface $helper,
        \Oracle\M2\Connector\Event\PlatformInterface $platform,
        \Oracle\M2\Connector\Event\SourceInterface $source,
        \Oracle\M2\Connector\MiddlewareInterface $middleware,
        \Oracle\M2\Core\Event\ManagerInterface $eventManager,
        \Oracle\M2\Core\App\EmulationInterface $appEmulation,
        \Oracle\M2\Helper\Data $mageHelper,
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
        $this->_middleware = $middleware;
        $this->_triggerManager = $triggerManager;
        $this->_statuses = $statuses;
        $this->_eventManager = $eventManager;
        $this->mageHelper = $mageHelper;
    }

    /**
     * @see parent
     */
    public function getSortOrder()
    {
        return 30;
    }

    /**
     * @see parent
     */
    public function getEndpointId()
    {
        return 'email';
    }

    /**
     * @see parent
     */
    public function getEndpointName()
    {
        return 'Messages';
    }

    /**
     * @see parent
     */
    public function getEndpointIcon()
    {
        return 'mage-icon-messages';
    }

    /**
     * Post setting sync to add reverse lookups
     *
     * @param mixed $observer
     * @return void
     */
    public function settingSync($observer)
    {
        $settings = $observer->getSettings();
        $scopeName = $observer->getScopeName();
        $scopeId = $observer->getScopeId();
        $this->_helper->createLookups($settings['objects'], $scopeName, $scopeId);
        $this->_helper->createReminders($settings['extensions'], $scopeName, $scopeId);
        foreach ($observer->getChildren() as $storeId) {
            $this->_helper->createLookups($settings['objects'], 'store', $storeId);
            $this->_helper->createReminders($settings['extensions'], 'store', $storeId);
        }
    }

    /**
     * Trigger remote Reminder/Post-Purchase delivery flusher
     *
     * @param mixed $observer
     * @return void
     */
    public function triggerFlush($observer)
    {
        $registration = $observer->getScript()->getRegistration();
        $cleanup = $this->_helper->getCleanupThreshold($registration->getScope(), $registration->getScopeId());
        $this->_triggerManager->deleteExpiredItems($registration->getConnectorKey(), $cleanup);
        if ($this->_triggerManager->hasItems($registration->getConnectorKey())) {
            $observer->getScript()->addScheduledTask('triggerFlush', ['sendNow' => true]);
        }
    }

    /**
     * Sends any scheduled reminder or post purchase message
     *
     * @param mixed $observer
     */
    public function flushMessages($observer)
    {
        $results = [
            'success' => 0,
            'error' => 0,
            'skipped' => 0
        ];
        $events = [];
        $messageLookup = [];
        $data = $observer->getScript()->getObject();
        $registration = $observer->getScript()->getRegistration();
        $customerEmail = null;
        $sendNow = true;
        if (array_key_exists('customerEmail', $data['data'])) {
            $customerEmail = $data['data']['customerEmail'];
        }
        if (array_key_exists('sendNow', $data['data'])) {
            $sendNow = $data['data']['sendNow'];
        }
        $batchSize = $this->_connectorSettings->getBatchSize($registration->getScope(), $registration->getScopeId());
        $triggerCollection = $this->_triggerManager->getApplicableTriggers($registration->getConnectorKey(), $customerEmail, $batchSize);
        $fidString = $this->mageHelper->getFireInstanceIdString();
        $this->logger->debug(
            "Flushing Trigger messages {$fidString}:"
            . "\n\tTrigger Collection query: {$triggerCollection->getSelectSql(true)}"
            . "\n\t{$triggerCollection->getSize()} Trigger(s) returned."
        );
        /** @var Trigger $trigger */
        foreach ($triggerCollection as $trigger) {

            $this->_appEmulation->startEnvironmentEmulation($trigger->getStoreId(), 'frontend', true);
            /** @var Trigger $model */
            $model = $this->_helper->getTriggerModel($trigger);
            if (is_null($model)) {
                $this->_triggerManager->delete($trigger);
                $this->_appEmulation->stopEnvironmentEmulation();
                $results['skipped']++;
                continue;
            }
            $triggerMessageKey = $this->getTriggerMessageKey($trigger);
            if (array_key_exists($triggerMessageKey, $messageLookup)) {
                $message = $messageLookup[$triggerMessageKey];
            } else {
                $message = $this->_helper->getMessage($trigger->getMessageType(), $trigger->getMessageId(), $trigger->getStoreId());
                if (empty($message)) {
                    $this->_triggerManager->delete($trigger);
                    $this->_appEmulation->stopEnvironmentEmulation();
                    $results['skipped']++;
                    continue;
                }
                $messageLookup[$triggerMessageKey] = $message;
            }
            $source = $this->_triggerManager->createSource($trigger, $message);
            $action = $source->action($model);
            if (!empty($action)) {
                $this->logger->debug(
                    "{$fidString} Sending {$trigger->getMessageType()} message with ID {$trigger->getMessageId()}"
                    . " to {$trigger->getCustomerEmail()}."
                );
                $event = $this->_platform->annotate($source, $model, $action, $trigger->getStoreId());
                if ($sendNow) {
                    if ($this->_platform->dispatch($event)) {
                        $this->_triggerManager->save($trigger->setSentMessage(1));
                        $results['success']++;
                    } else {
                        $results['error']++;
                    }
                } else {
                    $events[] = $event['data'];
                }
            } else {
                $this->_triggerManager->delete($trigger);
                $results['skipped']++;
            }
            $this->_appEmulation->stopEnvironmentEmulation();
        }
        $this->logger->debug(
            "Trigger Flush completed {$fidString}. Successes: {$results['success']}, Errors: {$results['error']}, Skipped: {$results['skipped']}"
        );
        $progresses = $sendNow ? $observer->getScript()->createProgresses($results) : $events;
        $observer->getScript()->setResults($progresses);
    }

    /**
     * Generates a unique trigger messsage key for the lookup map
     *
     * @param Trigger $trigger
     * @return string
     */
    protected function getTriggerMessageKey(Trigger $trigger)
    {
        return $trigger->getStoreId() . ':' . $trigger->getMessageType() . ':' . $trigger->getMessageId();
    }

    /**
     * @see parent
     */
    public function transformEvent($observer)
    {
        $transform = $observer->getTransform();
        $delivery = $transform->getDelivery();
        $event = $transform->getContext();
        $message = $event['message'];
        $context = $event['context'];
        $fields = $this->_helper->getExtraFields($message, $context, false);
        $delivery['fields'] = array_merge($delivery['fields'], $fields);
        $transform->setDelivery($delivery);
    }

    /**
     * @see parent
     */
    public function advancedAdditional($observer)
    {
        $options = [];
        foreach ([15, 30, 60] as $number) {
            $options[] = ['id' => $number, 'name' => $number];
        }
        $observer->getEndpoint()->addExtension([
            'id' => 'settings',
            'name' => 'Settings',
            'fields' => [
                [
                    'id' => 'sendThreshold',
                    'name' => 'Send Table Cleanup',
                    'type' => 'select',
                    'typeProperties' => [
                        'options' => $options,
                        'default' => 30
                    ]
                ]
            ]
        ]);

        $observer->getEndpoint()->addOptionToScript('event', 'jobName', [
            'id' => $this->getEndpointId(),
            'name' => 'Add or Update All Mapped System Messages'
        ]);

        $observer->getEndpoint()->addOptionToScript('event', 'moduleSettings', [
            'id' => $this->getEndpointId(),
            'name' => $this->getEndpointName()
        ]);

        $observer->getEndpoint()->addOptionToScript('event', 'jobName', [
            'id' => 'triggerFlush',
            'name' => 'Search Queued Messages'
        ]);

        $observer->getEndpoint()->addFieldToScript('event', [
            'id' => 'customerEmail',
            'name' => 'Customer Email',
            'type' => 'text',
            'depends' => [
                [ 'id' => 'jobName', 'values' => ['triggerFlush'] ]
            ]
        ]);

        $observer->getEndpoint()->addFieldToScript('event', [
            'id' => 'sendNow',
            'name' => 'Send Now',
            'type' => 'boolean',
            'typeProperties' => [ 'default' => false ],
            'depends' => [
                [ 'id' => 'jobName', 'values' => ['triggerFlush'] ]
            ]
        ]);

        $observer->getEndpoint()->addOptionToScript('test', 'jobName', [
            'id' => 'test_' . $this->getEndpointId(),
            'name' => 'Message'
        ]);

        $observer->getEndpoint()->addFieldToScript('test', [
            'id' => 'importSelected',
            'name' => 'Select Message',
            'type' => 'select',
            'position' => 3,
            'typeProperties' => [
                'objectType' => [
                    'extension' => 'email',
                    'id' => 'mapping'
                ]
            ],
            'depends' => [
                [
                    'id' => 'jobName',
                    'values' => [ 'test_' . $this->getEndpointId() ]
                ]
            ]
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
        $observer->getEndpoint()->addExtension([
            'id' => 'general',
            'name' => 'Settings',
            'fields' => $this->_generalOptions()
        ]);

        $observer->getEndpoint()->addSource([
            'id' => 'coupon_code',
            'name' => 'Specific Coupon',
            'filters' => [
                [
                    'id' => 'name',
                    'name' => 'Name',
                    'type' => 'text'
                ]
            ],
            'fields' => [
                [
                    'id' => 'id',
                    'name' => 'ID',
                    'width' => '2'
                ],
                [
                    'id' => 'name',
                    'name' => 'Name',
                    'width' => '4'
                ],
                [
                    'id' => 'code',
                    'name' => 'Coupon Code',
                    'width' => '4'
                ],
                [
                    'id' => 'active',
                    'name' => 'Active',
                    'width' => '2'
                ]
            ]
        ]);

        $this->_transactionalSettings($observer);
        $this->_reminderSettings($observer);
        $this->_reviewSettings($observer);
    }

    /**
     * Sets the transactional settings for the email module
     *
     * @param mixed $observer
     * @return void
     */
    protected function _transactionalSettings($observer)
    {
        $senderOptions = $this->_senderOptions();
        $sendTypes = $this->_sendTypes();
        $observer->getEndpoint()->addCategory([
            'id' => 'transactional',
            'name' => 'System'
        ]);
        $generalFields = $this->_generalOptions(['enabled' => true, 'advanced' => true]);
        $generalFields[] = [
            'id' => 'magentoBcc',
            'name' => 'Sales Email Copy Send Type',
            'type' => 'select',
            'typeProperties' => [
              'default' => 'magento',
              'options' => [
                  ['id' => 'magento', 'name' => 'Magento'],
                  ['id' => 'oracle', 'name' => 'Oracle']
              ]
            ],
            'depends' => [
                ['id' => 'enabled', 'values' => [true]]
            ]
        ];
        $observer->getEndpoint()->addExtension([
            'id' => 'transactional',
            'category' => 'transactional',
            'name' => 'General',
            'fields' => $generalFields
        ]);

        $customTemplates = $this->_selectableCustomTemplates();
        $objectFields = $this->_generalOptions([
            'advanced' => true,
            'messageExtras' => true,
            'depends' => [
                [
                    'id' => 'sendType',
                    'values' => [ 'triggered', 'transactional']
                ]
            ]
        ]);
        $objectFields[] = [
            'id' => 'name',
            'name' => 'Name',
            'type' => 'text',
            'required' => true,
        ];
        $objectFields[] = [
            'id' => 'configurationPath',
            'name' => 'Message Type',
            'type' => 'select',
            'required' => true,
            'typeProperties' => [
                'options' => $this->_selectableTemplates()
            ],
        ];
        if (!empty($customTemplates)) {
            $objectFields[] = [
                'id' => 'customContext',
                'name' => 'Template',
                'type' => 'select',
                'typeProperties' => [
                    'options' => $customTemplates,
                    'default' => 'defaultContext'
                ],
                'depends' => [
                    [
                        'id' => 'sendType',
                        'values' => [ 'triggered', 'transactional' ]
                    ]
                ]
            ];
        }
        $objectFields[] = [
            'id' => 'messageId',
            'name' => 'Message',
            'type' => 'select',
            'required' => true,
            'typeProperties' => [
                'oracle' => [ 'type' => 'message' ]
            ],
            'depends' => [
                [
                    'id' => 'sendType',
                    'values' => [ 'triggered', 'transactional']
                ]
            ]
        ];
        $objectFields[] = [
            'id' => 'sendType',
            'name' => 'Send Type',
            'type' => 'select',
            'required' => true,
            'typeProperties' => [
                'options' => $sendTypes,
                'default' => 'transactional'
            ],
        ];
        $observer->getEndpoint()->addObject([
            'id' => 'mapping',
            'category' => 'transactional',
            'name' => 'Message',
            'shortName' => 'Message',
            'identifiable' => true,
            'fields' => $objectFields
        ]);
    }

    /**
     * Sets the Reminder settings for the email module
     *
     * @param mixed $observer
     * @return void
     */
    protected function _reminderSettings($observer)
    {
        $emailIdentities = $this->_emailIdentities();
        $abandonUnits = $this->_abandonUnits();
        $targetAudience = $this->_targetAudience();
        $customerDefaults = [];
        foreach (array_slice($targetAudience, 0, 2) as $option) {
            $customerDefaults[] = $option['id'];
        }
        foreach (['cart', 'wishlist'] as $messageType) {
            $observer->getEndpoint()->addCategory([
                'id' => $messageType,
                'name' => ucfirst($messageType . 's')
            ]);
            $enabledDepends = [
                [
                    'id' => 'enabled',
                    'values' => [ true ]
                ]
            ];
            $generalOptions = $this->_generalOptions([
                'enabled' => true,
                'advanced' => true
            ]);
            $generalOptions[] = [
                'id' => 'sender',
                'name' => 'Email Sender',
                'type' => 'select',
                'depends' => $enabledDepends,
                'typeProperties' => [
                    'options' => $emailIdentities,
                    'default' => 'general'
                ]
            ];
            if ($messageType == 'cart') {
                $generalOptions[] = [
                    'id' => 'targetAudience',
                    'name' => 'Customer Group',
                    'type' => 'select',
                    'depends' => $enabledDepends,
                    'typeProperties' => [
                        'multiple' => true,
                        'options' => $targetAudience,
                        'default' => $customerDefaults
                    ],
                ];
            }
            $generalOptions[] = [
                'id' => 'exclusionLists',
                'name' => 'Exclusion Lists',
                'type' => 'select',
                'depends' => $enabledDepends,
                'typeProperties' => [
                    'multiple' => true,
                    'oracle' => [ 'type' => 'list' ]
                ]
            ];
            $observer->getEndpoint()->addExtension([
                'id' => $messageType,
                'category' => $messageType,
                'name' => 'General',
                'fields' => $generalOptions
            ]);

            $messageFields = $this->_generalOptions([
                'enabled' => true,
                'advanced' => true,
                'messageExtras' => true
            ]);
            $messageFields[] = [
                'id' => 'messageId',
                'name' => 'Message',
                'type' => 'select',
                'required' => true,
                'depends' => $enabledDepends,
                'typeProperties' => [
                    'oracle' => [ 'type' => 'message' ]
                ]
            ];
            if ($messageType == 'cart') {
                $messageFields[] = [
                    'id' => 'targetAudience',
                    'name' => 'Customer Group',
                    'type' => 'select',
                    'hasDefault' => true,
                    'advanced' => true,
                    'depends' => $enabledDepends,
                    'typeProperties' => [
                        'multiple' => true,
                        'options' => $targetAudience,
                    ]
                ];
            }
            $messageFields[] = [
                'id' => 'exclusionLists',
                'name' => 'Exclusion Lists',
                'type' => 'select',
                'hasDefault' => true,
                'advanced' => true,
                'depends' => $enabledDepends,
                'typeProperties' => [
                    'multiple' => true,
                    'oracle' => [ 'type' => 'list' ]
                ]
            ];
            $messageFields[] = [
                'id' => 'abandonUnit',
                'name' => 'Abandon Units',
                'type' => 'select',
                'depends' => $enabledDepends,
                'typeProperties' => [
                    'options' => $abandonUnits,
                    'default' => 'minutes'
                ]
            ];
            $messageFields[] = [
                'id' => 'abandonPeriod',
                'name' => 'Abandon Period',
                'type' => 'integer',
                'depends' => $enabledDepends,
                'typeProperties' => [
                    'default' => 30
                ]
            ];
            $messageFields[] = [
                'id' => 'sender',
                'name' => 'Email Sender',
                'type' => 'select',
                'hasDefault' => true,
                'advanced' => true,
                'depends' => $enabledDepends,
                'typeProperties' => [
                    'options' => $emailIdentities,
                ]
            ];
            foreach (range(1, 3) as $number) {
                $objectFields = $messageFields;
                if ($number == 1) {
                    $objectFields[] = [
                        'id' => 'sendType',
                        'name' => 'Send Type',
                        'type' => 'select',
                        'depends' => $enabledDepends,
                        'required' => true,
                        'typeProperties' => [
                            'options' => [
                                [ 'id' => 'transactional', 'name' => 'Transactional' ],
                                [ 'id' => 'triggered', 'name' => 'Marketing' ]
                            ],
                            'default' => 'transactional'
                        ]
                    ];
                }
                $observer->getEndpoint()->addExtension([
                    'id' => $messageType . '_' . $number,
                    'category' => $messageType,
                    'name' => "Message {$number}",
                    'fields' => $objectFields
                ]);
            }
        }
    }

    /**
     * Sets the Review settings for the email module
     *
     * @param mixed $observer
     * @return void
     */
    protected function _reviewSettings($observer)
    {
        $emailIdentities = $this->_emailIdentities();
        $targetAudience = $this->_targetAudience();
        $categories = $this->_productCategories();
        $customerDefaults = [];
        foreach (array_slice($targetAudience, 0, 2) as $option) {
            $customerDefaults[] = $option['id'];
        }
        $messages = [
            'review' => 'Reviews',
            'reorder' => 'Reorders',
            'caretip' => 'Care Tips'
        ];
        foreach ($messages as $messageType => $messageLabel) {
            $observer->getEndpoint()->addCategory([
                'id' => $messageType,
                'name' => $messageLabel
            ]);
            $enabledDepends = [
                [
                    'id' => 'enabled',
                    'values' => [ true ]
                ]
            ];
            $generalOptions = $this->_generalOptions([
                'enabled' => true,
                'advanced' => true,
                'messageExtras' => true
            ]);
            $generalOptions[] = [
                'id' => 'triggerStatus',
                'name' => 'Order Status',
                'type' => 'select',
                'depends' => $enabledDepends,
                'typeProperties' => [
                    'default' => 'complete',
                    'options' => $this->_statuses->getOptionArray()
                ],
            ];
            $generalOptions[] = [
                'id' => 'cancelStatus',
                'name' => 'Order Cancel Status',
                'type' => 'select',
                'depends' => $enabledDepends,
                'typeProperties' => [
                    'default' => ['holded', 'canceled', 'closed'],
                    'options' => $this->_statuses->getOptionArray(),
                    'multiple' => true
                ]
            ];
            if ($messageType == 'review') {
                $generalOptions[] = [
                    'id' => 'messageId',
                    'name' => 'Message',
                    'type' => 'select',
                    'typeProperties' => [
                        'oracle' => [ 'type' => 'message' ]
                    ],
                    'required' => true,
                    'depends' => $enabledDepends,
                ];
            }
            $generalOptions[] = [
                'id' => 'sendPeriod',
                'name' => 'Send Period',
                'type' => 'integer',
                'depends' => $enabledDepends,
                'typeProperties' => [
                    'default' => 14
                ],
            ];
            if ($messageType == 'reorder') {
                $generalOptions[] = [
                    'id' => 'multiply',
                    'name' => 'Send Period Per Unit',
                    'type' => 'boolean',
                    'depends' => $enabledDepends,
                    'typeProperties' => [
                        'default' => true
                    ]
                ];
            } elseif ($messageType == 'review') {
                $generalOptions[] = [
                    'id' => 'reviewForm',
                    'name' => 'Product URL Suffix',
                    'type' => 'text',
                    'depends' => $enabledDepends,
                    'typeProperties' => [
                        'default' => '#review-form'
                    ]
                ];
                $generalOptions[] = [
                    'id' => 'categories',
                    'name' => 'Include Categories',
                    'type' => 'select',
                    'depends' => $enabledDepends,
                    'typeProperties' => [
                        'multiple' => true,
                        'options' => $categories
                    ]
                ];
            }
            $generalOptions[] = [
                'id' => 'sender',
                'name' => 'Email Sender',
                'type' => 'select',
                'depends' => $enabledDepends,
                'typeProperties' => [
                    'options' => $emailIdentities,
                    'default' => 'general'
                ]
            ];
            $generalOptions[] = [
                'id' => 'targetAudience',
                'name' => 'Customer Group',
                'type' => 'select',
                'depends' => $enabledDepends,
                'typeProperties' => [
                    'multiple' => true,
                    'options' => $targetAudience,
                    'default' => $customerDefaults
                ],
            ];
            $generalOptions[] = [
                'id' => 'exclusionLists',
                'name' => 'Exclusion Lists',
                'type' => 'select',
                'depends' => $enabledDepends,
                'typeProperties' => [
                    'multiple' => true,
                    'oracle' => [ 'type' => 'list' ]
                ]
            ];
            $generalOptions[] = [
                'id' => 'exclusionCategories',
                'name' => 'Exclusion Categories',
                'type' => 'select',
                'depends' => $enabledDepends,
                'typeProperties' => [
                    'multiple' => true,
                    'options' => $categories
                ]
            ];
            $observer->getEndpoint()->addExtension([
                'id' => $messageType,
                'category' => $messageType,
                'name' => 'General',
                'fields' => $generalOptions
            ]);

            if (in_array($messageType, ['reorder', 'caretip'])) {
                $messageFields = $generalOptions;
                foreach ($messageFields as &$field) {
                    $field['advanced'] = true;
                    $field['hasDefault'] = true;
                    unset($field['depends']);
                    unset($field['required']);
                    unset($field['typeProperties']['default']);
                    if (empty($field['typeProperties'])) {
                        unset($field['typeProperties']);
                    }
                }
                array_unshift($messageFields, [
                    'id' => 'categories',
                    'name' => 'Include Categories',
                    'type' => 'select',
                    'required' => true,
                    'typeProperties' => [
                        'multiple' => true,
                        'options' => $categories
                    ]
                ]);
                if ($messageType == 'caretip') {
                    array_unshift($messageFields, [
                        'id' => 'content',
                        'name' => 'Content',
                        'type' => 'textarea',
                        'required' => true
                    ]);
                }
                array_unshift($messageFields, [
                    'id' => 'messageId',
                    'name' => 'Message',
                    'type' => 'select',
                    'required' => true,
                    'typeProperties' => [
                        'oracle' => [ 'type' => 'message' ]
                    ]
                ]);
                array_unshift($messageFields, [
                    'id' => 'name',
                    'name' => 'Name',
                    'type' => 'text',
                    'required' => true
                ]);
                $observer->getEndpoint()->addObject([
                    'id' => $messageType,
                    'category' => $messageType,
                    'name' => 'Message',
                    'fields' => $messageFields
                ]);
            }
        }
    }

    /**
     * Gets a collection of application abandons
     *
     * @return array
     */
    protected function _targetObjects()
    {
        return [
            [
                'id' => 'both',
                'name' => 'Both Carts and Wishlists',
            ],
            [
                'id' => 'cart',
                'name' => 'Carts Only'
            ],
            [
                'id' => 'wishlist',
                'name' => 'Wishlists Only'
            ]
        ];
    }

    /**
     * Gets the general fields for each section
     *
     * @param boolean $advanced
     * @return array
     */
    protected function _generalOptions($options = [])
    {
        $generalFields = [];
        if (isset($options['enabled'])) {
            $field = [
                'id' => 'enabled',
                'name' => 'Enabled',
                'type' => 'boolean',
                'advanced' => isset($options['enabledAdvanced']),
                'hasDefault' => isset($options['enabledAdvanced']),
            ];
            if (!isset($options['enabledAdvanced'])) {
                $field['typeProperties'] = [ 'default' => false];
            }
            if (isset($options['depends'])) {
                $field['depends'] = $options['depends'];
            }
            $generalFields[] = $field;
        }
        foreach ($this->_senderOptions() as $option) {
            $field = [
                'id' => $option['id'],
                'name' => $option['name'],
                'type' => 'boolean',
                'advanced' => isset($options['advanced']),
                'hasDefault' => isset($options['advanced'])
            ];
            if (!isset($options['advanced'])) {
                $field['typeProperties'] = [ 'default' => true ];
            }
            if (isset($options['depends'])) {
                $field['depends'] = $options['depends'];
            }
            $generalFields[] = $field;
        }
        $otherOptions = [
            'replyTo' => ['text', 'Reply-To'],
            'displaySymbol' => ['boolean', 'Display Currency Symbol'],
            'includeTax' => ['boolean', 'Include Tax in Prices']
        ];
        foreach ($otherOptions as $id => $tuple) {
            list($type, $label) = $tuple;
            $field = [
                'id' => $id,
                'name' => $label,
                'type' => $type,
                'advanced' => isset($options['advanced']),
                'hasDefault' => isset($options['advanced'])
            ];
            if (isset($options['depends'])) {
                $field['depends'] = $options['depends'];
            }
            if ($type == 'boolean' && !isset($options['advanced'])) {
                $field['typeProperties'] = [ 'default' => false ];
            }
            $generalFields[] = $field;
        }
        if (isset($options['messageExtras'])) {
            $container = new \Oracle\M2\Core\DataObject();
            $container->setFields([]);
            $container->setOptions($options);
            $this->_eventManager->dispatch('oracle_email_message_extras', [
                'container' => $container
            ]);
            $generalFields = array_merge($generalFields, $container->getFields());
        }
        return $generalFields;
    }

    /**
     * Gets the sender flags for the discovery endpoint
     *
     * @return array
     */
    protected function _senderOptions()
    {
        return [
            [
                'id' => 'authentication',
                'name' => 'Sender Authentication'
            ],
            [
                'id' => 'fatigueOverride',
                'name' => 'Fatigue Override'
            ],
            [
                'id' => 'replyTracking',
                'name' => 'Reply Tracking'
            ],
        ];
    }

    /**
     * Gets the abandon units
     *
     * @return array
     */
    protected function _abandonUnits()
    {
        return [
            [
                'id' => 'minutes',
                'name' => 'Minutes'
            ],
            [
                'id' => 'hours',
                'name' => 'Hours'
            ],
            [
                'id' => 'days',
                'name' => 'Days'
            ]
        ];
    }

    /**
     * Gets a Connector based collection for the Magento send types
     *
     * @return array
     */
    protected function _sendTypes()
    {
        return [
            [
                'id' => 'magento',
                'name' => 'Magento'
            ],
            [
                'id' => 'nosend',
                'name' => 'Do Not Send'
            ],
            [
                'id' => 'transactional',
                'name' => 'Transactional'
            ],
            [
                'id' => 'triggered',
                'name' => 'Marketing'
            ],
        ];
    }

    /**
     * Generates a list of selectable templates to import
     *
     * @return array
     */
    protected function _selectableTemplates()
    {
        $importOptions = [];
        foreach ($this->_defaultTemplates() as $template) {
            if ($template['group'] == 'Magento_Email' || !preg_match('/template$/', $template['value'])) {
                continue;
            }
            $importOptions[] = [
                'id' => $template['value'],
                'name' => $template['label']
            ];
        }
        return $importOptions;
    }

    /**
     * Transforms custom templates into Connector options
     *
     * @return array
     */
    protected function _selectableCustomTemplates()
    {
        $templateOptions = [
            [
                'id' => 'defaultContext',
                'name' => 'Configured Template'
            ]
        ];
        foreach ($this->_customTemplates() as $template) {
            $templateOptions[] = [
                'id' => $template->getId(),
                'name' => $template->getTemplateCode()
            ];
        }
        return $templateOptions;
    }

    /**
     * @see parent
     */
    protected function _sendTest($registration, $data)
    {
        $objects = [];
        if (array_key_exists('importSelected', $data)) {
            list($scopeName, $scopeId) = explode('.', $data['scopeId']);
            $storeId = $this->_middleware->defaultStoreId($scopeName, $scopeId);
            $mappingId = $data['importSelected'];
            $template = $this->_helper->getMessage('mapping', $mappingId, $storeId, true);
            if (!empty($template)) {
                $templateId = $this->_helper->getTemplateId($template);
                $message = $this->_helper->replaceTemplate($templateId, ['store' => $storeId]);
                $objects[] = new \Oracle\M2\Common\DataObject([
                    'storeId' => $storeId,
                    'message' => $message,
                    'template' => $template
                ]);
            }
        }
        return $objects;
    }

    /**
     * @see parent
     * @return \Oracle\M2\Common\DataObject[]
     */
    protected function _sendHistorical($registration, $data)
    {
        $objects = [];
        $startTime = null;
        list($scopeName, $scopeId) = explode('.', $data['options']['scopeId']);
        $storeId = $this->_middleware->defaultStoreId($scopeName, $scopeId);
        $store = $this->_storeManager->getStore($storeId);
        $lookups = $this->_helper->getActiveObjects('mapping', $store, true);
        foreach ($lookups as $template) {
            $templateId = $this->_helper->getTemplateId($template);
            $message = $this->_helper->replaceTemplate($templateId, ['store' => $storeId]);
            $objects[] = new \Oracle\M2\Common\DataObject([
                'storeId' => $storeId,
                'message' => $message,
                'template' => $template
            ]);
        }
        return $objects;
    }

    /**
     * @see parent
     */
    protected function _applyLimitOffset($objects, $limit, $offset)
    {
        return $objects;
    }

    /**
     * Available templates on the server
     *
     * @return array
     */
    abstract protected function _defaultTemplates();

    /**
     * Gets the avaialble templates on the server
     *
     * @return array
     */
    abstract protected function _customTemplates();

    /**
     * Gets a collection of available email identities
     *
     * @return array
     */
    abstract protected function _emailIdentities();

    /**
     * Gets a collection of available customer groups
     *
     * @return array
     */
    abstract protected function _targetAudience();

    /**
     * Gets a collection of product categories
     *
     * @return array
     */
    abstract protected function _productCategories();
}

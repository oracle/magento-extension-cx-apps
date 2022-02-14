<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Coupon;

abstract class ExtensionAbstract
    extends \Oracle\M2\Connector\Discovery\AdvancedExtensionAbstract
    implements \Oracle\M2\Connector\Discovery\GroupInterface, 
        \Oracle\M2\Email\FilterEventInterface
{
    use \Oracle\M2\Email\FilterEventTrait;

    /** @var \Oracle\M2\Core\Sales\RuleManagerInterface */
    protected $_rules;

    /** @var \Oracle\M2\Coupon\ManagerInterface */
    protected $_manager;

    /** @var \Oracle\M2\Connector\MiddlewareInterface */
    protected $_middleware;

    /**
     * @param \Oracle\M2\Core\Sales\RuleManagerInterface $rules
     * @param ManagerInterface $manager
     * @param \Oracle\M2\Connector\MiddlewareInterface $middleware
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
        \Oracle\M2\Core\Sales\RuleManagerInterface $rules,
        ManagerInterface $manager,
        \Oracle\M2\Connector\MiddlewareInterface $middleware,
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
        $this->_middleware = $middleware;
        $this->_rules = $rules;
        $this->_manager = $manager;
    }

    /**
     * @see parent
     */
    public function getSortOrder()
    {
        return 80;
    }

    /**
     * @see parent
     */
    public function getEndpointName()
    {
        return 'Coupons';
    }

    /**
     * @see parent
     */
    public function getEndpointId()
    {
        return 'coupon';
    }

    /**
     * @see parent
     */
    public function getEndpointIcon()
    {
        return 'mage-icon-coupons';
    }

    /**
     * @see parent
     */
    public function gatherEndpoints($observer)
    {
        $observer->getDiscovery()->addGroupHelper($this);
    }

    /**
     * Gets coupon pools from the platform
     *
     * @param mixed $observer
     * @return void
     */
    public function pullCouponPools($observer)
    {
        $this->_pullCoupons($observer->getSource(), true);
    }

    /**
     * Gets coupon codes from the platform
     *
     * @param mixed $observer
     * @return void
     */
    public function pullCouponSpecific($observer)
    {
        $this->_pullCoupons($observer->getSource(), false);
    }

    /**
     * Trigger Middleware to replenish pools
     *
     * @param mixed $observer
     * @return void
     */
    public function triggerReplenish($observer)
    {
        $script = $observer->getScript();
        $poolIds = $this->_manager->getReplenishablePoolIds($script->getRegistration());
        if (!empty($poolIds)) {
            $script->addScheduledTask('triggerReplenish', [
                'importSelected' => implode(',', $poolIds),
                'autoTriggered' => true,
                'replenishTimes' => 1
            ]);
        }
    }

    /**
     * Replenishes coupons using associated generator
     *
     * @param mixed $observer
     * @return void
     */
    public function replenishCoupons($observer)
    {
        $results = [
            'success' => 0,
            'error' => 0,
            'skipped' => 0,
            'couponManagerUploaded' => 0
        ];
        $script = $observer->getScript()->getObject();
        $data = $script['data'];
        $registration = $observer->getScript()->getRegistration();
        $autoTriggered = array_key_exists('autoTriggered', $data) ? $data['autoTriggered'] : false;
        $times = array_key_exists('replenishTimes', $data) ? $data['replenishTimes'] : 1;
        if ($data['requestId'] < $times) {
            $storeId = $this->_middleware->defaultStoreId($registration->getScope(), $registration->getScopeId());
            if (!$autoTriggered || $this->_helper->isEnabled('store', $storeId)) {
                foreach ($this->_registeredGenerators($registration, $data) as $generator) {
                    $coupons = $this->_manager->acquireCoupons($generator);
                    $updatedAmount = count($coupons);
                    if ($generator['integration']) {
                        $coupons = new \Oracle\M2\Common\DataObject(
                            [
                                'campaignId' => $generator['campaignId'],
                                'ruleId' => $generator['ruleId'],
                                'coupons' => $coupons
                            ]
                        );
                        $action = $this->_source->action($coupons);
                        if (!empty($action)) {
                            $event = $this->_platform->annotate($this->_source, $coupons, $action, $storeId);
                            if ($this->_platform->dispatch($event) || $this->_queueManager->save($event)) {
                                $results['couponManagerUploaded'] += $updatedAmount;
                            }
                        }
                    }
                    $results['success'] += $updatedAmount;
                }
            }
        }
        $observer->getScript()->setProgress($results);
    }

    /**
     * Observe the Oracle redirect to apply coupons
     *
     * @param mixed $observer
     * @return void
     */
    public function applyCoupon($observer)
    {
        $store = $this->_storeManager->getStore(true);
        if ($this->_helper->isEnabled('store', $store)) {
            $this->_helper->applyCodeFromRequest($observer->getMessages(), $store);
        }
        if ($this->_helper->isForced()) {
            $observer->getRedirect()->setIsReferer(true);
        } else {
            $allParams = $observer->getRedirect()->getParams();
            foreach ($this->_helper->getParams('store', $store) as $stripped) {
                if (array_key_exists($stripped, $allParams)) {
                    unset($allParams[$stripped]);
                }
            }
            $observer->getRedirect()->setParams($allParams);
        }
    }

    /**
     * @see parent
     */
    public function advancedAdditional($observer)
    {
        $observer->getEndpoint()->addOptionToScript("historical", "jobName", [
            'id' => $this->getEndpointId(),
            'name' => 'Coupon'
        ]);

        $observer->getEndpoint()->addFieldToScript('historical', [
            'id' => 'importSelected',
            'name' => 'Select Generator',
            'type' => 'select',
            'typeProperties' => [
                'objectType' => [
                    'extension' => 'coupon',
                    'id' => 'generator',
                ]
            ],
            'depends' => [
                [
                    'id' => 'jobName',
                    'values' => [ $this->getEndpointId() ]
                ]
            ]
        ]);

        $observer->getEndpoint()->addFieldToScript('historical', [
            'id' => 'codePrefix',
            'name' => 'Code Prefix Filter',
            'type' => 'text',
            'depends' => [
                [
                    'id' => 'jobName',
                    'values' => [ $this->getEndpointId() ]
                ]
            ]
        ]);

        $observer->getEndpoint()->addFieldToScript('historical', [
            'id' => 'codeSuffix',
            'name' => 'Code Suffix Filter',
            'type' => 'text',
            'depends' => [
                [
                    'id' => 'jobName',
                    'values' => [ $this->getEndpointId() ]
                ]
            ]
        ]);

        $observer->getEndpoint()->addOptionToScript('event', 'jobName', [
            'id' => 'triggerReplenish',
            'name' => 'Replenish Coupon Pool',
        ]);

        $observer->getEndpoint()->addOptionToScript('event', 'moduleSettings', [
            'id' => $this->getEndpointId(),
            'name' => $this->getEndpointName()
        ]);

        $observer->getEndpoint()->addFieldToScript('event', [
            'id' => 'importSelected',
            'name' => 'Select Generator',
            'type' => 'select',
            'typeProperties' => [
                'objectType' => [
                    'extension' => 'coupon',
                    'id' => 'generator',
                ]
            ],
            'depends' => [
                [
                    'id' => 'jobName',
                    'values' => [ 'triggerReplenish' ]
                ]
            ]
        ]);
    }

    /**
     * Observe the Oracle redirect to apply coupons
     *
     * @param mixed $observer
     * @return void
     */
    public function applyCodeOnCartAfterItem($observer)
    {
        $product = $observer->getProduct();
        if ($this->_helper->isEnabled('store', $product->getStoreId())) {
            $this->_helper->applyCode();
        }
    }

    /**
     * @see parent
     */
    public function endpointInfo($observer)
    {
        $couponFormats = $this->_couponFormats();
        $observer->getEndpoint()->addSource([
            'id' => 'coupon_pool',
            'name' => 'Rule Pool',
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
                    'id' => 'type',
                    'name' => 'Coupon Type',
                    'width' => '4'
                ],
                [
                    'id' => 'active',
                    'name' => 'Active',
                    'width' => '2'
                ]
            ]
        ]);

        $observer->getEndpoint()->addObject([
            'id' => 'generator',
            'name' => 'Coupon Generator',
            'shortName' => 'Coupons',
            'identifiable' => true,
            'fields' => [
                [
                    'id' => 'name',
                    'name' => 'Name',
                    'type' => 'text',
                    'required' => true,
                    'position' => 1,
                ],
                [
                    'id' => 'enabled',
                    'name' => 'Enabled',
                    'type' => 'boolean',
                    'required' => true,
                    'position' => 2,
                    'typeProperties' => [
                        'default' => false
                    ]
                ],
                [
                    'id' => 'ruleId',
                    'name' => 'Shopping Cart Price Rule',
                    'type' => 'source',
                    'required' => true,
                    'position' => 3,
                    'typeProperties' => [
                        'source' => 'coupon_pool'
                    ]
                ],
                [
                    'id' => 'format',
                    'name' => 'Code Format',
                    'type' => 'select',
                    'required' => true,
                    'position' => 6,
                    'typeProperties' => [
                        'default' => $couponFormats[0]['id'],
                        'options' => $couponFormats
                    ]
                ],
                [
                    'id' => 'length',
                    'name' => 'Code Length',
                    'type' => 'integer',
                    'required' => true,
                    'position' => 7,
                    'typeProperties' => [
                        'default' => 12,
                        'min' => 1,
                        'max' => 32
                    ]
                ],
                [
                    'id' => 'prefix',
                    'name' => 'Code Prefix',
                    'type' => 'text',
                    'position' => 8,
                ],
                [
                    'id' => 'suffix',
                    'name' => 'Code Suffix',
                    'type' => 'text',
                    'position' => 9,
                ],
                [
                    'id' => 'dashInterval',
                    'name' => 'Dash Every X Characters',
                    'type' => 'integer',
                    'position' => 10,
                    'typeProperties' => [
                        'default' => 0,
                        'min' => 0,
                        'max' => 31
                    ]
                ],
                [
                    'id' => 'integration',
                    'name' => 'Coupon Campaign Sync',
                    'type' => 'boolean',
                    'typeProperties' => [
                        'default' => false
                    ]
                ],
                [
                    'id' => 'campaignId',
                    'name' => 'Coupon Campaign',
                    'type' => 'select',
                    'required' => true,
                    'typeProperties' => [
                        'oracle' => [ 'type' => 'couponManager' ]
                    ],
                    'depends' => [
                        [ 'id' => 'integration', 'values' => [true]]
                    ]
                ],
                [
                    'id' => 'threshold',
                    'name' => 'Replenish Threshold',
                    'type' => 'integer',
                    'typeProperties' => [
                        'default' => 1000
                    ],
                    'required' => true,
                    'depends' => [
                        [ 'id' => 'integration', 'values' => [true]]
                    ]
                ],
                [
                    'id' => 'amount',
                    'name' => 'Replenish Amount',
                    'type' => 'integer',
                    'required' => true,
                    'typeProperties' => [
                        'default' => 100,
                        'min' => 1,
                        'max' => 1000
                    ],
                    'depends' => [
                        [ 'id' => 'integration', 'values' => [true]]
                    ]
                ],
                [
                    'id' => 'endDate',
                    'name' => 'Replenish Until',
                    'type' => 'date',
                    'depends' => [
                        [ 'id' => 'integration', 'values' => [true]]
                    ]
                ],
            ]
        ]);

        $observer->getEndpoint()->addExtension([
            'id' => 'settings',
            'name' => 'Settings',
            'fields' => [
                [
                    'id' => 'enabled',
                    'name' => 'Enabled',
                    'type' => 'boolean',
                    'required' => true,
                    'typeProperties' => [
                        'default' => false,
                    ]
                ],
                [
                    'id' => 'coupon_param',
                    'name' => 'Coupon Code Query Parameter',
                    'type' => 'text',
                    'required' => true,
                    'typeProperties' => [
                        'default' => 'coupon'
                    ],
                    'depends' => [
                        [
                            'id' => 'enabled',
                            'values' => [true]
                        ]
                    ]
                ],
                [
                    'id' => 'invalid_param',
                    'name' => 'Invalid Coupon Query Parameter',
                    'type' => 'text',
                    'required' => true,
                    'typeProperties' => [
                        'default' => 'invalid_coupon'
                    ],
                    'depends' => [
                        [
                            'id' => 'enabled',
                            'values' => [true]
                        ]
                    ]
                ],
                [
                    'id' => 'display_message',
                    'name' => 'Display Message',
                    'type' => 'boolean',
                    'required' => true,
                    'typeProperties' => [
                        'default' => false
                    ],
                    'depends' => [
                        [
                            'id' => 'enabled',
                            'values' => [true]
                        ]
                    ]
                ],
                [
                    'id' => 'success_message',
                    'name' => 'Success Message',
                    'type' => 'textarea',
                    'required' => true,
                    'typeProperties' => [
                        'default' => 'Coupon {code} was successfully applied to your shopping session.'
                    ],
                    'depends' => [
                        [
                            'id' => 'enabled',
                            'values' => [true]
                        ],
                        [
                            'id' => 'display_message',
                            'values' => [true]
                        ]
                    ]
                ],
                [
                    'id' => 'invalid_message',
                    'name' => 'Invalid Message',
                    'type' => 'textarea',
                    'required' => true,
                    'typeProperties' => [
                        'default' => 'Coupon {code} is invalid.'
                    ],
                    'depends' => [
                        [
                            'id' => 'enabled',
                            'values' => [true]
                        ],
                        [
                            'id' => 'display_message',
                            'values' => [true]
                        ]
                    ]
                ],
                [
                    'id' => 'depleted_message',
                    'name' => 'Depleted Message',
                    'type' => 'textarea',
                    'required' => true,
                    'typeProperties' => [
                        'default' => 'Coupon {code} has been depleted.'
                    ],
                    'depends' => [
                        [
                            'id' => 'enabled',
                            'values' => [true]
                        ],
                        [
                            'id' => 'display_message',
                            'values' => [true]
                        ]
                    ]
                ],
                [
                    'id' => 'expired_message',
                    'name' => 'Expired Message',
                    'type' => 'textarea',
                    'required' => true,
                    'typeProperties' => [
                        'default' => 'Coupon {code} has expired.'
                    ],
                    'depends' => [
                        [
                            'id' => 'enabled',
                            'values' => [true]
                        ],
                        [
                            'id' => 'display_message',
                            'values' => [true]
                        ]
                    ]
                ],
                [
                    'id' => 'conflict_message',
                    'name' => 'Conflict Message',
                    'type' => 'textarea',
                    'required' => true,
                    'typeProperties' => [
                        'default' => 'Your shopping session already has coupon {oldCode} applied. {link} to apply {newCode} instead.',
                    ],
                    'depends' => [
                        [
                            'id' => 'enabled',
                            'values' => [true]
                        ],
                        [
                            'id' => 'display_message',
                            'values' => [true]
                        ]
                    ]
                ],
                [
                    'id' => 'link_text',
                    'name' => 'Link Text',
                    'type' => 'text',
                    'required' => true,
                    'typeProperties' => [
                        'default' => 'Click here',
                    ],
                    'depends' => [
                        [
                            'id' => 'enabled',
                            'values' => [true]
                        ],
                        [
                            'id' => 'display_message',
                            'values' => [true]
                        ]
                    ]
                ],
            ]
        ]);
    }

    /**
     * Adds coupons to the message editor
     *
     * @param mixed $observer
     */
    public function messageExtras($observer)
    {
        $options = $observer->getContainer()->getOptions();
        $fields = $observer->getContainer()->getFields();
        $couponType = [
            'id' => 'couponType',
            'name' => 'Coupon Type',
            'type' => 'select',
            'advanced' => isset($options['advanced']),
            'typeProperties' => [
                'options' => [
                    [ 'id' => 'none', 'name' => 'No Coupon' ],
                    [ 'id' => 'specific', 'name' => 'Specific Coupon' ],
                    [ 'id' => 'generator', 'name' => 'Generator' ]
                ]
            ]
        ];
        if (!isset($options['advanced'])) {
            $couponType['typeProperties']['default'] = 'none';
        }
        $fields[] = $couponType;
        $fields[] = [
            'id' => 'ruleId',
            'name' => 'Specific Coupon',
            'type' => 'source',
            'required' => true,
            'advanced' => isset($options['advanced']),
            'depends' => [
                [ 'id' => 'couponType', 'values' => ['specific'] ]
            ],
            'typeProperties' => [
                'source' => 'coupon_code'
            ]
        ];
        $fields[] = [
            'id' => 'generatorId',
            'name' => 'Coupon Generator',
            'type' => 'object',
            'required' => true,
            'advanced' => isset($options['advanced']),
            'depends' => [
                [ 'id' => 'couponType', 'values' => ['generator'] ]
            ],
            'typeProperties' => [
                'objectType' => [
                    'extension' => 'coupon',
                    'id' => 'generator'
                ]
            ]
        ];
        $observer->getContainer()->setFields($fields);
    }

    /**
     * @see parent
     */
    public function apply(array $message, array $templateVars = [], $forceContext)
    {
        $ret = [];
        if (array_key_exists('couponType', $message)) {
            $ret = [ 'coupon' => $message['couponType'] ];
            if (!$forceContext) {
                $couponCode = '';
                switch ($message['couponType']) {
                    case 'generator':
                        $couponCode = '';
                        if (isset($message['generatorId'])) {
                            $couponCode = $this->_manager->acquireCoupon($message['generatorId']);
                        }
                        break;
                    case 'specific':
                        $rule = null;
                        if (isset($message['ruleId'])) {
                            $rule = $this->_rules->getById($message['ruleId']);
                        }
                        if ($rule) {
                            $coupon = $rule->getPrimaryCoupon();
                            if ($coupon) {
                                $couponCode = $coupon->getCode();
                            }
                        }
                }
                $ret = ['couponCode' => $couponCode];
            }
        }
        return $ret;
    }

    /**
     * Implementors fill this in
     *
     * @return array
     */
    abstract protected function _couponFormats();

    /**
     * @see parent
     * @return \Iterator|[]
     */
    protected function _sendHistorical($registration, $data)
    {
        $objects = [];
        $fromDate = null;
        if (array_key_exists('startTime', $data)) {
            $startTime = $data['startTime'];
            if ($startTime) {
                $fromDate = strtotime($startTime);
            }
        }
        $toDate = null;
        if (array_key_exists('endTime', $data)) {
            $endTime = $data['endTime'];
            if ($endTime) {
                $toDate = strtotime($endTime);
            }
        }
        if (array_key_exists('options', $data)) {
            $codePrefix = null;
            if (!empty($data['options']['codePrefix'])) {
                $codePrefix = $data['codePrefix'];
            }
            $codeSuffix = null;
            if (!empty($data['options']['codeSuffix'])) {
                $codeSuffix = $data['codeSuffix'];
            }
        }
        return new CouponGenerationIterator(
            $this->_middleware,
            $this->_rules,
            $this->_registeredGenerators($registration, $data['options']),
            $fromDate,
            $toDate,
            $codePrefix,
            $codeSuffix
        );
    }

    /**
     * @see parent
     */
    protected function _applyLimitOffset($objects, $limit, $offset)
    {
        return $objects->setLimit($limit)->setOffset($offset);
    }

    /**
     * @see parent
     */
    protected function _sendTest($registration, $data)
    {
        return [];
    }

    /**
     * Gets all of the generators from a registration
     *
     * @return array
     */
    protected function _registeredGenerators($registration, $data)
    {
        $generators = [];
        if (!empty($data['importSelected'])) {
            foreach (explode(',', $data['importSelected']) as $generatorId) {
                $generator = $this->_manager->getById($generatorId, true);
                if (empty($generator)) {
                    continue;
                }
                $generators[] = $generator;
            }
        }
        return $generators;
    }

    /**
     * Gets coupon pools or codes from the platform
     *
     * @param \Oracle\M2\Connector\Discovery\Source $source
     * @param boolean $onlyPools
     */
    protected function _pullCoupons($source, $onlyPools)
    {
        $results = [];
        foreach ($this->_rules->getBySource($source, $onlyPools) as $rule) {
            $type = $rule->getCouponType() == 3 ?
                'Auto Generation' :
                'Specific Coupon';
            if ($rule->getUseAutoGeneration()) {
                $type .= " (Auto)";
            }
            $result = [
                'id' => $rule->getId(),
                'name' => $rule->getName(),
                'active' => $rule->getIsActive() ?
                    'Yes' :
                    'No'
            ];
            if ($onlyPools) {
                $result['type'] = $type;
            } else {
                $result['code'] = $rule->getPrimaryCoupon()->getCode();
            }
            $results[] = $result;
        }
        $source->setResults($results);
    }
}

<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Order;

use Oracle\M2\Connector\Discovery\AdvancedExtensionAbstract;
use Oracle\M2\Connector\Event\SourceInterface;
use Oracle\M2\Core\Sales\OrderCacheInterface;
use Magento\Framework\Api\Filter;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\Data\OrderSearchResultInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;

abstract class ExtensionAbstract
    extends \Oracle\M2\Connector\Discovery\AdvancedExtensionAbstract
    implements \Oracle\M2\Connector\Discovery\GroupInterface,
        \Oracle\M2\Connector\Discovery\TransformEventInterface,
        \Oracle\M2\Email\FilterEventInterface
{

    use \Oracle\M2\Email\FilterEventTrait;

    const CONNECTOR_CONTROLLER_NAME = 'connector';

    /** @var \Oracle\M2\Core\Catalog\ProductAttributeCacheInterface */
    protected $_attributes;

    /** @var \Oracle\M2\Core\Sales\OrderStatusesInterface */
    protected $_statuses;

    /** @var \Oracle\M2\Core\Sales\OrderCacheInterface */
    protected $_orderRepo;

    /** @var  \Oracle\M2\Helper\Data */
    protected $mageHelper;

    /** @var SearchCriteriaBuilder */
    protected $criteriaBuilder;

    /** @var FilterBuilder */
    protected $filterBuilder;

    /** @var FilterGroupBuilder */
    protected $filterGroupBuilder;

    /** @var OrderRepositoryInterface */
    protected $orderRepo;

    /** @var \Psr\Log\LoggerInterface */
    protected $logger;

    /**
     * @param \Oracle\M2\Core\Catalog\ProductAttributeCacheInterface $attributes
     * @param \Oracle\M2\Core\Sales\OrderStatusesInterface $statuses
     * @param \Oracle\M2\Core\Sales\OrderCacheInterface $orderRepo
     * @param \Oracle\M2\Core\App\EmulationInterface $appEmulation
     * @param \Oracle\M2\Core\Store\ManagerInterface $storeManager
     * @param \Oracle\M2\Connector\QueueManagerInterface $queueManager
     * @param \Oracle\M2\Connector\SettingsInterface $connectorSettings
     * @param \Oracle\M2\Connector\Event\HelperInterface $helper
     * @param \Oracle\M2\Connector\Event\PlatformInterface $platform
     * @param SourceInterface $source
     * @param \Oracle\M2\Helper\Data $mageHelper
     * @param SearchCriteriaBuilder $criteriaBuilder
     * @param FilterBuilder $filterBuilder
     * @param FilterGroupBuilder $filterGroupBuilder
     * @param OrderRepositoryInterface $orderRepository
     * @param \Magento\Framework\Filesystem\DriverInterface $fileSystemDriver
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Oracle\M2\Core\Catalog\ProductAttributeCacheInterface $attributes,
        \Oracle\M2\Core\Sales\OrderStatusesInterface $statuses,
        \Oracle\M2\Core\Sales\OrderCacheInterface $orderRepo,//TODO:Replace this with Magento's order repo (added below)
        \Oracle\M2\Core\App\EmulationInterface $appEmulation,
        \Oracle\M2\Core\Store\ManagerInterface $storeManager,
        \Oracle\M2\Connector\QueueManagerInterface $queueManager,
        \Oracle\M2\Connector\SettingsInterface $connectorSettings,
        \Oracle\M2\Order\SettingsInterface $helper,
        \Oracle\M2\Connector\Event\PlatformInterface $platform,
        \Oracle\M2\Connector\Event\SourceInterface $source,
        \Oracle\M2\Helper\Data $mageHelper,
        SearchCriteriaBuilder $criteriaBuilder,
        FilterBuilder $filterBuilder,
        FilterGroupBuilder $filterGroupBuilder,
        OrderRepositoryInterface $orderRepository,
        \Magento\Framework\Filesystem\DriverInterface $fileSystemDriver,
        \Psr\log\LoggerInterface $logger
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
        $this->_statuses = $statuses;
        $this->_orderRepo = $orderRepo;
        $this->mageHelper = $mageHelper;
        $this->criteriaBuilder = $criteriaBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->filterGroupBuilder = $filterGroupBuilder;
        $this->orderRepo = $orderRepository;
    }

    /**
     * @see parent
     */
    public function getSortOrder()
    {
        return 15;
    }

    /**
     * @see parent
     */
    public function getEndpointId()
    {
        return 'order';
    }

    /**
     * @see parent
     */
    public function getEndpointName()
    {
        return 'Orders';
    }

    /**
     * @see parent
     */
    public function getEndpointIcon()
    {
        return 'mage-icon-orders';
    }

    /**
     * @return OrderCacheInterface
     */
    public function getOrderRepo()
    {
        return $this->_orderRepo;
    }

    /**
     * @return SourceInterface
     */
    public function getSource()
    {
        return $this->_source;
    }

    /**
     * @see parent
     */
    public function transformEvent($observer)
    {
        $data = [];
        $transform = $observer->getTransform();
        $event = $transform->getContext();
        $order = $this->getOrderRepo()->getById($event['id']);
        if (!$order) {
            throw new \RuntimeException("Order ID " . $event['id'] . " not found.");
        } elseif (!$this->getSource()->action($order)) {
            $this->logger->debug('Order event cannot be processed for ID ' . $event['id']
                . '. The status \''. $order->getStatus() . '\' is not actionable.');
        }

        $data = $this->_source->transform(clone $order);
        $transform->setOrder($data);
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
                    'id' => 'status',
                    'name' => 'Oracle Order Status',
                    'type' => 'select',
                    'required' => true,
                    'requiredFeatures' => [ 'enableOrderService' => true ],
                    'typeProperties' => [
                        'default' => 'PROCESSED',
                        'options' => [
                            [
                                'id' => 'PENDING',
                                'name' => 'Pending',
                            ],
                            [
                                'id' => 'PROCESSED',
                                'name' => 'Processed',
                            ]
                        ]
                    ],
                ],
                [
                    'id' => 'import_status',
                    'name' => 'Orders to Import',
                    'type' => 'select',
                    'required' => true,
                    'typeProperties' => [
                        'default' => ['pending', 'complete', 'processing'],
                        'options' => $this->_statuses->getOptionArray(),
                        'multiple' => true
                    ],
                ],
                [
                    'id' => 'delete_status',
                    'name' => 'Orders to Delete',
                    'type' => 'select',
                    'required' => true,
                    'typeProperties' => [
                        'default' => ['holded', 'canceled', 'closed'],
                        'options' => $this->_statuses->getOptionArray(),
                        'multiple' => true
                    ],
                ],
                [
                    'id' => 'price',
                    'name' => 'Product Price',
                    'type' => 'select',
                    'required' => true,
                    'typeProperties' => [
                        'default' => 'display',
                        'options' => [
                            [ 'id' => 'display', 'name' => 'Display' ],
                            [ 'id' => 'base', 'name' => 'Base' ]
                        ]
                    ]
                ],
                [
                    'id' => 'description',
                    'name' => 'Product Description',
                    'type' => 'select',
                    'required' => true,
                    'typeProperties' => [
                        'default' => 'description',
                        'options' => [
                            [
                                'id' => 'short_description',
                                'name' => 'Short Description',
                            ],
                            [
                                'id' => 'description',
                                'name' => 'Description',
                            ]
                        ]
                    ]
                ],
                [
                    'id' => 'include_discount',
                    'name' => 'Include Discount',
                    'type' => 'boolean',
                    'requiredFeatures' => [ 'enableOrderService' => false ],
                    'typeProperties' => [
                        'default' => false
                    ]
                ],
                [
                    'id' => 'include_tax',
                    'name' => 'Include Tax',
                    'type' => 'boolean',
                    'requiredFeatures' => [ 'enableOrderService' => false ],
                    'typeProperties' => [
                        'default' => false
                    ]
                ],
                [
                    'id' => 'include_shipping',
                    'name' => 'Include Shipping',
                    'type' => 'boolean',
                    'requiredFeatures' => [ 'enableOrderService' => false ],
                    'typeProperties' => [
                        'default' => false
                    ]
                ],
                [
                    'id' => 'image_type',
                    'name' => 'Image View Type',
                    'type' => 'select',
                    'required' => true,
                    'typeProperties' => [
                        'default' => 'image',
                        'options' => [
                            [
                                'id' => 'image',
                                'name' => 'Base Image',
                            ],
                            [
                                'id' => 'small_image',
                                'name' => 'Small Image',
                            ],
                            [
                                'id' => 'thumbnail',
                                'name' => 'Thumbnail'
                            ]
                        ]
                    ]
                ],
                [
                    'id' => 'other_field',
                    'name' => 'Other Field',
                    'type' => 'select',
                    'requiredFeatures' => [ 'enableOrderService' => true ],
                    'typeProperties' => [
                        'options' => $this->_attributes->getOptionArray()
                    ]
                ]
            ]
        ]);

        $observer->getEndpoint()->addAutoConfigData(
            $this->getEndpointId(),
            $observer->getRegistration()->getScopeHash(),
            $this->getAutoConfigData('order')
        );
    }

    /**
     * @see parent
     */
    public function advancedAdditional($observer)
    {
        $observer->getEndpoint()->addOptionToScript('test', 'jobName', [
            'id' => 'test_' . $this->getEndpointId() . '_new',
            'name' => 'Order'
        ]);

        $observer->getEndpoint()->addFieldToScript('test', [
            'id' => 'customerOrderId',
            'name' => 'Order ID',
            'type' => 'text',
            'position' => 10,
            'depends' => [
                [
                    'id' => 'jobName',
                    'values' => ['test_' . $this->getEndpointId() . '_new']
                ]
            ]
        ]);

        $observer->getEndpoint()->addOptionToScript('historical', 'jobName', [
            'id' => $this->getEndpointId(),
            'name' => $this->getEndpointName()
        ]);

        $observer->getEndpoint()->addFieldToScript('historical', [
            'id' => 'maintainConversions',
            'name' => 'Maintain Conversions',
            'type' => 'boolean',
            'required' => true,
            'position' => 5,
            'typeProperties' => [ 'default' => false ],
            'requiredFeatures' => [ 'enableOrderService' => true ],
            'depends' => [
                [
                    'id' => 'jobName',
                    'values' => [$this->getEndpointId()]
                ]
            ]
        ]);

        $observer->getEndpoint()->addOptionToScript('event', 'moduleSettings', [
            'id' => $this->getEndpointId(),
            'name' => $this->getEndpointName()
        ]);

        if ($observer->getRegistration()->getEnvironment() == 'SANDBOX') {
            $observer->getEndpoint()->addFieldToScript('historical', [
                'id' => 'performDelete',
                'name' => 'Delete Orders from Oracle',
                'type' => 'boolean',
                'position' => 6,
                'required' => true,
                'typeProperties' => [ 'default' => false ],
                'depends' => [
                    [ 'id' => 'jobName', 'values' => [$this->getEndpointId()] ]
                ]
            ]);
        }
    }

    /**
     * @see \Oracle\M2\Connector\Discovery\ExtensionPushEventAbstract::pushChanges
     */
    public function pushChanges($observer)
    {
        $foreground = !$this->mageHelper->invokedByConnector();
        parent::pushChanges($observer, $foreground);
    }

    /**
     * @see parent
     */
    protected function _historicalAction($data, $object)
    {
        $action = parent::_historicalAction($data, $object);

        // If action modifiers were specified in the connector
        if (isset($data['options'])) {
            if ($action == SourceInterface::ADD_ACTION && isset($data['options']['maintainConversions'])) {
                $action = $data['options']['maintainConversions'] ?
                    SourceInterface::UPDATE_ACTION :
                    SourceInterface::REPLACE_ACTION;
            } elseif ($action == SourceInterface::DELETE_ACTION
                && (!isset($data['options']['performDelete']) || !$data['options']['performDelete'])) {
                $action = null;
            }
        }

        return $action;
    }

    /**
     * @see parent
     */
    protected function _sendTest($registration, $data)
    {
        if (array_key_exists('customerOrderId', $data)) {
            $customerOrderId = $data['customerOrderId'];
            $scopeFilter = $this->getScopeFilter($data);
            if ($scopeFilter) {
                $this->criteriaBuilder->addFilter($scopeFilter->getField(), $scopeFilter->getValue());
            }
            $this->criteriaBuilder->addFilter('increment_id', $customerOrderId);
        } else {
            return [];
        }
        return $this->getList($this->criteriaBuilder->setPageSize(1)->create());
    }

    /**
     * @see parent
     * @return OrderSearchResultInterface
     */
    protected function _sendHistorical($registration, $data)
    {
        $orders = $this->_collection();
        if (array_key_exists('startTime', $data)) {
            $startTime = $data['startTime'];
            if ($startTime) {
                $orders->addFieldToFilter('updated_at', ['gt' => $startTime]);
            }
        }
        if (array_key_exists('endTime', $data)) {
            $endTime = $data['endTime'];
            if ($endTime) {
                $orders->addFieldToFilter('updated_at', ['lt' => $endTime]);
            }
        }
        return $this->_attachScopeFilter($data['options'], $orders);
    }

    /**
     * Override the parent method as this should already be applied.
     *
     * @see AdvancedExtensionAbstract::_applyLimitOffset()
     * @param OrderSearchResultInterface $objects
     * @param int $limit
     * @param int $offset
     * @return OrderSearchResultInterface
     */
    protected function _applyLimitOffset($objects, $limit, $offset) {

        if (method_exists($objects, 'getSelectSql')) {
            $this->logger->info('select query again: ' . $objects->getSelectSql(true) . '$offset = '.$offset. '$limit = '.$limit);
        }

        $objects->getSelect()->limitPage($offset, $limit);
        return $objects;
    }

    /**
     * @param SearchCriteria $searchCriteria
     * @return OrderSearchResultInterface
     */
    private function getList(SearchCriteria $searchCriteria) {
        $this->logger->info("Executing getList on the order repository in " . ExtensionAbstract::class
            . $this->getMemoryUseageString());
        $filtersString = "Order repo query filters:";
        foreach($searchCriteria->getFilterGroups() as $filterGroup) {
           foreach ($filterGroup->getFilters() as $filter) {
               $value = is_string($filter->getValue()) ? $filter->getValue() : implode(', ', $filter->getValue());
               $filtersString .=
                   "\n" . $filter->getField() . ' ' . $filter->getConditionType() . ' ' . $value;
           }
        }
        $this->logger->info($filtersString);

        $order = $this->_collection();

        /** @var OrderSearchResultInterface $searchResult */
        $searchResult = $this->orderRepo->getList($searchCriteria);

        $this->logger->info("Completed getList call on the order repository in " . ExtensionAbstract::class
            . $this->getMemoryUseageString());

        return $searchResult;
    }

    /**
     * @return string
     */
    private function getMemoryUseageString() {
       return "Current memory usage: " . memory_get_usage() / 1000000000 . " Gb. "
        . "Peak usage: " . memory_get_peak_usage() / 1000000000 . " Gb";
    }

    /**
     * Attaches store scope filters as needed
     *
     * @param $data
     * @param mixed $orders
     * @return mixed
     */
    protected function _attachScopeFilter($data, $orders)
    {
        list($scopeName, $scopeId) = explode('.', $data['scopeId']);
        switch ($scopeName) {
            case 'website':
                $storeIds = [];
                $website = $this->_storeManager->getWebsite($scopeId);
                foreach ($website->getStores() as $store) {
                    $storeIds[] = $store->getId();
                }
                return $orders->addFieldToFilter('store_id', array('in' => $storeIds));
            case 'store':
                return $orders->addFieldToFilter('store_id', ['eq' => $scopeId]);
        }
        return $orders;
    }

    /**
     * @param array $data
     * @return Filter|null
     */
    protected function getScopeFilter($data)
    {
        list($scopeName, $scopeId) = explode('.', $data['scopeId']);
        switch ($scopeName) {
            case 'website':
                $storeIds = [];
                $website = $this->_storeManager->getWebsite($scopeId);
                foreach ($website->getStores() as $store) {
                    $storeIds[] = $store->getId();
                }
                return $this->filterBuilder->setField('store_id')
                    ->setConditionType('in')
                    ->setValue($storeIds)
                    ->create();
            case 'store':
                return $this->filterBuilder->setField('store_id')
                    ->setConditionType('eq')
                    ->setValue($scopeId)
                    ->create();
        }
        return null;
    }

    /**
     * @see FilterEventInterface::apply
     * @param [] $message Message data
     * @param [] $templateVars
     * @param bool $forceContext
     */
    public function apply(array $message, array $templateVars = [], $forceContext)
    {
        $fields = [];
        if (!isset($templateVars['order'])) {
            return $fields;
        }

        /** @var Order $order */
        $order = $templateVars['order'];

        /** @var \Oracle\M2\Order\SettingsInterface $helper */
        $helper = $this->getHelper();
        $helper->setCurrency($order->getOrderCurrencyCode());

        $useDisplaySymbol = isset($message['displaySymbol']) ? $message['displaySymbol'] : true;
        $includeTax = isset($message['includeTax']) ? $message['includeTax'] : false;
        $subtotal = $includeTax ? $order->getSubtotalInclTax() : $order->getSubtotal();
        $shippingAmt = $includeTax ? $order->getBaseShippingInclTax() : $order->getBaseShippingAmount();
        $fields['subtotal'] = $helper->formatPrice($subtotal, $useDisplaySymbol);
        $fields['shippingAmt'] = $helper->formatPrice($shippingAmt, $useDisplaySymbol);
        $fields['discount'] = $helper->formatPrice($order->getDiscountAmount(), $useDisplaySymbol);
        $fields['tax'] = $helper->formatPrice($order->getTaxAmount(), $useDisplaySymbol);
        $fields['grandTotal'] = $helper->formatPrice($order->getGrandTotal(), $useDisplaySymbol);
        $index = 1;

        /** @var \Magento\Sales\Model\Order\Item $lineItem */
        foreach ($order->getAllVisibleItems() as $lineItem) {
            $product = $lineItem->getProduct();
            $price = $helper->formatPrice(
                $helper->getItemPrice($lineItem, false, $includeTax), $useDisplaySymbol
            );
            $rowTotal = $helper->formatPrice(
                $helper->getItemRowTotal($lineItem, false, $includeTax), $useDisplaySymbol
            );
            $fields["productId_{$index}"] = $lineItem->getProductId();
            $fields["productName_{$index}"] = $product->getName();
            $fields["productSku_{$index}"] = $lineItem->getSku();
            $fields["productPrice_{$index}"] = $price;
            $fields["productTotal_{$index}"] = $rowTotal;
            $fields["productQty_{$index}"] = $lineItem->getQtyOrdered();
            $fields["productUrl_{$index}"] = $product->getProductUrl();
            $fields["productDescription_{$index}"] = $helper->getItemDescription($lineItem);
            $index++;
        }
        return $fields;
    }

    /**
     * Gets a new order collection to filter down
     *
     * @return \Iterator
     */
    abstract protected function _collection();
}

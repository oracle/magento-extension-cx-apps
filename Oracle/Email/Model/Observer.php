<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Email\Model;

class Observer extends \Oracle\M2\Email\ExtensionAbstract
{
    protected $_emailConfig;
    protected $_emailTemplates;
    protected $_identities;
    protected $_groupRepo;
    protected $_searchBuilder;
    protected $_categoryManagement;

    /**
     * @param \Oracle\M2\Core\Sales\OrderStatusesInterface $statuses
     * @param \Magento\Email\Model\Template\Config $emailConfig
     * @param \Magento\Email\Model\ResourceModel\Template\CollectionFactory $emailTemplates
     * @param \Magento\Config\Model\Config\Source\Email\Identity $identities
     * @param \Magento\Customer\Api\GroupRepositoryInterface $groupRepo
     * @param \Magento\Catalog\Api\CategoryManagementInterface $categoryManagement
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchBuilder
     * @param \Oracle\M2\Email\TriggerManagerInterface $triggerManager
     * @param \Oracle\M2\Core\Store\ManagerInterface $storeManager
     * @param \Oracle\M2\Connector\QueueManagerInterface $queueManager
     * @param \Oracle\M2\Connector\SettingsInterface $connectorSettings
     * @param \Oracle\M2\Connector\Event\PlatformInterface $platform
     * @param \Oracle\M2\Email\Event\Source $source
     * @param \Oracle\M2\Email\SettingsInterface $helper
     * @param \Oracle\M2\Connector\MiddlewareInterface $middleware
     * @param \Oracle\M2\Core\Event\ManagerInterface $eventManager
     * @param \Oracle\M2\Core\App\EmulationInterface $appEmulation
     * @param \Oracle\M2\Helper\Data $mageHelper
     * @param \Magento\Framework\Filesystem\DriverInterface $fileSystemDriver
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Oracle\M2\Core\Sales\OrderStatusesInterface $statuses,
        \Magento\Email\Model\Template\Config $emailConfig,
        \Magento\Email\Model\ResourceModel\Template\CollectionFactory $emailTemplates,
        \Magento\Config\Model\Config\Source\Email\Identity $identities,
        \Magento\Customer\Api\GroupRepositoryInterface $groupRepo,
        \Magento\Catalog\Api\CategoryManagementInterface $categoryManagement,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchBuilder,
        \Oracle\M2\Email\TriggerManagerInterface $triggerManager,
        \Oracle\M2\Core\Store\ManagerInterface $storeManager,
        \Oracle\M2\Connector\QueueManagerInterface $queueManager,
        \Oracle\M2\Connector\SettingsInterface $connectorSettings,
        \Oracle\M2\Connector\Event\PlatformInterface $platform,
        \Oracle\M2\Email\Event\Source $source,
        \Oracle\M2\Email\SettingsInterface $helper,
        \Oracle\M2\Connector\MiddlewareInterface $middleware,
        \Oracle\M2\Core\Event\ManagerInterface $eventManager,
        \Oracle\M2\Core\App\EmulationInterface $appEmulation,
        \Oracle\M2\Helper\Data $mageHelper,
        \Magento\Framework\Filesystem\DriverInterface $fileSystemDriver,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::__construct(
            $triggerManager,
            $statuses,
            $storeManager,
            $queueManager,
            $connectorSettings,
            $helper,
            $platform,
            $source,
            $middleware,
            $eventManager,
            $appEmulation,
            $mageHelper,
            $fileSystemDriver,
            $logger
        );
        $this->_emailConfig = $emailConfig;
        $this->_emailTemplates = $emailTemplates;
        $this->_identities = $identities;
        $this->_categoryManagement = $categoryManagement;
        $this->_groupRepo = $groupRepo;
        $this->_searchBuilder = $searchBuilder;
    }

    /**
     * @see parent
     */
    public function translate($message)
    {
        return __($message);
    }

    /**
     * @see parent
     */
    protected function _defaultTemplates()
    {
        return $this->_emailConfig->getAvailableTemplates();
    }

    /**
     * @see parent
     */
    protected function _customTemplates()
    {
        return $this->_emailTemplates->create();
    }

    /**
     * @see paernt
     */
    protected function _emailIdentities()
    {
        $identities = [];
        foreach ($this->_identities->toOptionArray() as $option) {
            $identities[] = [
                'id' => $option['value'],
                'name' => $option['label']
            ];
        }
        return $identities;
    }

    /**
     * @see parent
     */
    protected function _targetAudience()
    {
        $groups = [];
        $list = $this->_groupRepo->getList($this->_searchBuilder->create());
        foreach ($list->getItems() as $group) {
            $groups[] = [
                'id' => $group->getId(),
                'name' => $group->getCode()
            ];
        }
        return $groups;
    }

    /**
     * @see parent
     */
    protected function _productCategories()
    {
        $root = $this->_categoryManagement->getTree(1);
        $options = [];
        $this->_flatCategories($options, $root->getChildrenData());
        return $options;
    }

    /**
     * Flattens the tree structure of the categories
     *
     * @param array &$$options
     * @param mixed $categories
     * @return void
     */
    protected function _flatCategories(&$options, $categories)
    {
        if ($categories) {
            foreach ($categories as $category) {
                $options[] = [
                    'id' => $category->getId(),
                    'name' => $category->getName()
                ];
                $this->_flatCategories($options, $category->getChildrenData());
            }
        }
    }
}

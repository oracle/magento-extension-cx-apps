<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Email\Impl;

class Trigger implements \Oracle\M2\Email\TriggerManagerInterface
{
    protected $_dataFactory;
    protected $_triggerFactory;
    protected $_objectManager;

    /**
     * @param \Oracle\Email\Model\ResourceModel\Trigger\CollectionFactory $dataFactory
     * @param \Oracle\Email\Model\TriggerFactory $triggerFactory
     */
    public function __construct(
        \Oracle\Email\Model\ResourceModel\Trigger\CollectionFactory $dataFactory,
        \Oracle\Email\Model\TriggerFactory $triggerFactory,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->_dataFactory = $dataFactory;
        $this->_triggerFactory = $triggerFactory;
        $this->_objectManager = $objectManager;
    }

    /**
     * @see parent
     */
    public function hasItems($siteId)
    {
        return $this->_latestTriggers($siteId, false)->getSize() > 0;
    }

    /**
     * @see parent
     */
    public function save(\Oracle\M2\Email\TriggerInterface $trigger)
    {
        $trigger->save();
    }

    /**
     * @see parent
     */
    public function getTriggers($siteId, $modelType, $modelId)
    {
        $results = [];
        $collection = $this->_dataFactory->create()
            ->addFieldToFilter('site_id', ['eq' => $siteId])
            ->addFieldToFilter('model_type', ['eq' => $modelType])
            ->addFieldToFilter('model_id', ['eq' => $modelId]);
        foreach ($collection as $result) {
            $results[$result->getMessageId()] = $result;
        }
        return $results;
    }

    /**
     * @see parent
     */
    public function createTrigger($siteId, $messageType, $messageId)
    {
        return $this->_triggerFactory->create([
            'data' => [
                'sent_message' => 0,
                'site_id' => $siteId,
                'message_type' => $messageType,
                'message_id' => $messageId
            ]
        ]);
    }

    /**
     * @see parent
     */
    public function getApplicableTriggers($siteId, $customerEmail = null, $limit = null, $messageType = null)
    {
        if (is_null($limit)) {
            $limit = self::LIMIT;
        }
        $collection = $this->_latestTriggers($siteId, $customerEmail)->setPageSize($limit);
        if (!is_null($messageType)) {
            $collection->addFieldToFilter('message_type', ['eq' => $messageType]);
        }
        return $collection;
    }

    /**
     * @see parent
     */
    public function delete(\Oracle\M2\Email\TriggerInterface $trigger)
    {
        $trigger->delete();
    }

    /**
     * @see parent
     */
    public function deleteExpiredItems($siteId, $daysInThePast = null)
    {
        if (is_null($daysInThePast)) {
            $daysInThePast = self::DAYS_THRESHOLD;
        }
        $this->_dataFactory->create()->deleteExpiredItems($siteId, $daysInThePast);
    }

    /**
     * @see parent
     */
    public function createSource($trigger, $message)
    {
        $classPath = 'Oracle\M2\Email\Event\Trigger\%s';
        $className = sprintf($classPath, ucfirst($trigger->getMessageType()));
        return $this->_objectManager->create($className, [
            'message' => $message,
            'trigger' => $trigger
        ]);
    }

    /**
     * Gets triggers that haven't been sent yet
     *
     * @param string $siteId
     * @param string $customerEmail
     * @return \Oracle\Email\Model\ResourceModel\Trigger\Collection
     */
    protected function _latestTriggers($siteId, $customerEmail)
    {
        $nowGmt = date('Y-m-d H:i:s');
        $triggers = $this->_dataFactory->create()
            ->addFieldToFilter('site_id', ['eq' => $siteId])
            ->addFieldToFilter('sent_message', ['eq' => '0']);
        if (empty($customerEmail)) {
            $triggers->addFieldToFilter('triggered_at', [ 'lt' => $nowGmt ]);
        } else {
            $triggers->addFieldToFilter('customer_email', [ 'eq' => $customerEmail ]);
        }
        return $triggers;
    }
}

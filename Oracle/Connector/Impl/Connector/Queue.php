<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Connector\Impl\Connector;

class Queue implements \Oracle\M2\Connector\QueueManagerInterface
{
    protected $_time;
    protected $_eventFactory;
    protected $_eventData;

    /**
     * @var \Oracle\Connector\Helper\Queue
     */
    protected $_helper;

    /**
     * Event data types and their keys that should not change between updates.
     *
     * @var array
     */
    protected static $_fixedPropertiesMapping = [
        'order' => ['tid' => 0]
    ];

    /**
     * @param \Oracle\Connector\Model\EventFactory $eventFactory
     * @param \Oracle\Connector\Model\ResourceModel\Event\CollectionFactory $eventData
     */
    public function __construct(
        \Magento\Framework\Stdlib\DateTime\DateTime $time,
        \Oracle\Connector\Model\EventFactory $eventFactory,
        \Oracle\Connector\Model\ResourceModel\Event\CollectionFactory $eventData,
        \Oracle\Connector\Helper\Data $helper
    ) {
        $this->_time = $time;
        $this->_eventFactory = $eventFactory;
        $this->_eventData = $eventData;
        $this->_helper = $helper;
    }

    /**
     * @see parent
     */
    public function hasItems($siteId)
    {
        return $this->_eventData->create()
            ->addFieldToFilter('site_id', ['eq' => $siteId])
            ->getSize() > 0;
    }

    /**
     * @see parent
     */
    public function save($newEventData)
    {
        $eventType = $newEventData['data']['type'];
        /** @var \Oracle\Connector\Model\Event $queue */
        $queue = $this->_eventFactory->create();
        if (array_key_exists('context', $newEventData['data']) && array_key_exists('event', $newEventData['data']['context'])) {
            if (array_key_exists('uniqueKey', $newEventData['data']['context']['event'][$eventType])) {
                $eventType = $newEventData['data']['context']['event'][$eventType]['uniqueKey'];
                $queue->loadByEventType($newEventData['data']['account']['siteId'], $eventType);
                $previousEventData = $queue->getEventData();
                if ($previousEventData) {
                    $unserializedEventData = @unserialize($previousEventData);
                    $previousEventData = ($previousEventData == serialize(false) || $unserializedEventData !== false)
                        ? $unserializedEventData
                        : $previousEventData;
                    ;
                    if (isset($previousEventData['data']['context']['event'])) {
                        $newEventData['data']['context']['event'] = $this->_helper->preserveFixedProperties(
                            static::$_fixedPropertiesMapping,
                            $previousEventData['data']['context']['event'],
                            $newEventData['data']['context']['event']
                        );
                    }
                }
            }
        }
        // The serialize() function converts a storable representation of a value.
        $queue->setEventType($eventType)
            ->setSiteId($newEventData['data']['account']['siteId'])
            ->setEventData(serialize($newEventData))
            ->setCreatedAt($this->_time->gmtDate())
            ->save();
    }

    /**
     * @see parent
     */
    public function getOldestEvents($siteId, $limit = null, $type = null)
    {
        if (is_null($limit)) {
            $limit = self::LIMIT;
        }
        $events = $this->_eventData->create()
            ->addFieldToFilter('site_id', ['eq' => $siteId]);
        if (!is_null($type)) {
            $events->addFieldToFilter('event_type', ['eq' => $type]);
        }
        $events->setPageSize($limit)->setOrder('created_at', 'ASC');
        return $events;
    }

    /**
     * @see parent
     */
    public function delete(\Oracle\M2\Connector\QueueInterface $queue)
    {
        $queue->delete();
    }

    /**
     * @see parent
     */
    public function deleteByIds(array $queueIds)
    {
        $events = $this->_eventData->create();
        $events->addFieldToFilter('entity_id', ['in' => $queueIds]);
        foreach ($events as $event) {
            $this->delete($event);
        }
    }
}

<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Connector\Model;

class Event extends \Magento\Framework\Model\AbstractModel implements \Oracle\M2\Connector\QueueInterface
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Oracle\Connector\Model\ResourceModel\Event');
    }

    /**
     * @see parent
     */
    public function getEventData()
    {
        return $this->getData(self::EVENT_DATA);
    }

    /**
     * @see parent
     */
    public function getEventType()
    {
        return $this->getData(self::EVENT_TYPE);
    }

    /**
     * @see parent
     */
    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * @see parent
     */
    public function getSiteId()
    {
        return $this->getData(self::SITE_ID);
    }

    /**
     * Attempts to load an existing event by the uniqueKey type
     *
     * @param string $siteId
     * @param string $eventType
     * @return self
     */
    public function loadByEventType($siteId, $eventType)
    {
        $this->_getResource()->loadByEventType($this, $siteId, $eventType);
        return $this;
    }
}

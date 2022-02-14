<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Connector;

interface QueueInterface
{
    const EVENT_TYPE = 'event_type';
    const EVENT_DATA = 'event_data';
    const SITE_ID = 'site_id';
    const CREATED_AT = 'created_at';

    /**
     * Gets the site hash belonging to this registration
     *
     * @return string
     */
    public function getSiteId();

    /**
     * Gets the serialized information for the event
     *
     * @return string
     */
    public function getEventData();

    /**
     * Gets the type of event this queue belongs to
     *
     * @return string
     */
    public function getEventType();

    /**
     * Gets the creation timestamp of the event
     *
     * @return string
     */
    public function getCreatedAt();
}

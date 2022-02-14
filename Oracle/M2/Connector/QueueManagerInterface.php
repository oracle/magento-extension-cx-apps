<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Connector;

interface QueueManagerInterface
{
    const LIMIT = 25;

    /**
     * Determines if there are any entries in the queue
     *
     * @param string $siteId
     * @return bool
     */
    public function hasItems($siteId);

    /**
     * Writes an event to the queue
     *
     * @param array $event
     * @return bool
     */
    public function save($event);

    /**
     * Gets the oldest events to flush from the manager
     *
     * @param string $siteId
     * @param int $limit
     * @param string $type
     * @return \Iterator
     */
    public function getOldestEvents($siteId, $limit = null, $type = null);

    /**
     * Directly deletes the queue entry
     *
     * @param QueueInterface $queue
     * @return void
     */
    public function delete(QueueInterface $queue);

    /**
     * Deletes any queue entries by these ids
     *
     * @param array $queueIds
     * @return void
     */
    public function deleteByIds(array $queueIds);
}

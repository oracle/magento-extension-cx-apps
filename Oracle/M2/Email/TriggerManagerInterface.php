<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Email;

interface TriggerManagerInterface
{
    const LIMIT = 25;
    const DAYS_THRESHOLD = 30;

    /**
     * Determines if there are any messages in the trigger queue
     *
     * @param string $siteId
     * @return bool
     */
    public function hasItems($siteId);

    /**
     * Writes a trigger to the queue
     *
     * @param TriggerInterface $trigger
     * @return TriggerInterface
     */
    public function save(TriggerInterface $trigger);

    /**
     * Returns a collection of all recorded triggers for
     * the provided model
     *
     * @param string $siteId
     * @param string $modelType
     * @param mixed $modelId
     * @return mixed
     */
    public function getTriggers($siteId, $modelType, $modelId);

    /**
     * Creates a new trigger with the given type
     *
     * @param string $siteId
     * @param string $messageType
     * @param string $messageId
     * @return TriggerInterface
     */
    public function createTrigger($siteId, $messageType, $messageId);

    /**
     * Gets all of the triggers ready to be sent
     *
     * @param string $siteId
     * @param mixed $customerEmail
     * @param int $limit
     * @param string $messageType
     * @return \Oracle\Email\Model\ResourceModel\Trigger\Collection
     */
    public function getApplicableTriggers($siteId, $customerEmail = null, $limit = null, $messageType = null);

    /**
     * Deletes the trigger from the queue
     *
     * @param TriggerInterface $trigger
     * @return void
     */
    public function delete(TriggerInterface $trigger);


    /**
     * Deletes the trigger entries from the queue older than this date
     *
     * @param string $siteId
     * @param int $daysInthePast
     * @return void
     */
    public function deleteExpiredItems($siteId, $daysInthePast = null);

    /**
     * Creates an event source for the message and message type
     *
     * @param TriggerInterface $trigger
     * @param array $message
     * @return \Oracle\M2\Connector\Event\SourceInterface
     */
    public function createSource($trigger, $message);
}

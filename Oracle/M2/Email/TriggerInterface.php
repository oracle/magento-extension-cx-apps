<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Email;

interface TriggerInterface
{
    const FIELD_TRIGGERED_AT = 'triggered_at';
    const FIELD_SITE_ID = 'site_id';
    const FIELD_STORE_ID = 'store_id';
    const FIELD_MODEL_TYPE = 'model_type';
    const FIELD_MODEL_ID = 'model_id';
    const FIELD_MESSAGE_ID = 'message_id';
    const FIELD_MESSAGE_TYPE = 'message_type';
    const FIELD_SENT_MESSAGE = 'sent_message';
    const FIELD_CUSTOMER_EMAIL = 'customer_email';

    /**
     * Gets the site hash associated with this delivery
     *
     * @return string
     */
    public function getSiteId();

    /**
     * Gets the store ID that the action occurred on
     *
     * @return mixed
     */
    public function getStoreId();

    /**
     * Gets the timestamp when the trigger should be sent
     *
     * @return mixed
     */
    public function getTriggeredAt();

    /**
     * Gets the model type associated with this trigger
     *
     * @return string
     */
    public function getModelType();

    /**
     * Gets the model id associated with this trigger
     *
     * @return string
     */
    public function getModelId();

    /**
     * Gets the object id associated with this trigger
     *
     * @return string
     */
    public function getMessageId();

    /**
     * Gets the message type associated with this trigger
     *
     * @return string
     */
    public function getMessageType();

    /**
     * Gets the email address potentially associated with this triggger
     *
     * @return mixed
     */
    public function getCustomerEmail();

    /**
     * Determines if this message has been sent
     *
     * @return int
     */
    public function getSentMessage();

    /**
     * Sets when message is sent
     *
     * @param int $value
     * @return $this
     */
    public function setSentMessage($value);

    /**
     * Sets the email address associated with the trigger
     *
     * @param string $email
     * @return $this
     */
    public function setCustomerEmail($email);

    /**
     * Sets the new triggered at time
     *
     * @param mixed $newTime
     * @return $this
     */
    public function setTriggeredAt($newTime);

    /**
     * Sets the model_type and model_id from the provided model
     *
     * @param string $modelType
     * @param mixed $modelId
     * @param mixed $storeId
     * @return $this
     */
    public function setModel($modelType, $modelId, $storeId);
}

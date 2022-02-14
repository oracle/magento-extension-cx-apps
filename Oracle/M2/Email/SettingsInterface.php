<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Email;

interface SettingsInterface extends \Oracle\M2\Connector\Event\HelperInterface
{
    const XML_PATH_CLEANUP_THRESHOLD = 'oracle/advanced/extensions/settings/sendThreshold';
    const XML_PATH_MAGENTO_BCC = 'oracle/email/extensions/transactional/magentoBcc';
    const XML_PATH_MESSAGE_ENABLED = 'oracle/email/extensions/%s/enabled';
    const XML_PATH_MESSAGE_DISPLAY = 'oracle/email/extensions/%s/displaySymbol';
    const XML_PATH_MESSAGE_INCLUDE_TAX = 'oracle/email/extensions/%s/includeTax';
    const XML_PATH_MESSAGE_AUTHENTICATION = 'oracle/email/extensions/%s/authentication';
    const XML_PATH_MESSAGE_FATIGUE_OVERRIDE = 'oracle/email/extensions/%s/fatigueOverride';
    const XML_PATH_MESSAGE_REPLY_TRACKING = 'oracle/email/extensions/%s/replyTracking';
    const XML_PATH_MESSAGE_REPLY_TO = 'oracle/email/extensions/%s/replyTo';
    const XML_PATH_MESSAGE_SENDER = 'oracle/email/extensions/%s/sender';
    const XML_PATH_MESSAGE_AUDIENCE = 'oracle/email/extensions/%s/targetAudience';
    const XML_PATH_MESSAGE_EXCLUSION_LISTS = 'oracle/email/extensions/%s/exclusionLists';
    const XML_PATH_POSTPURCHASE_FIELD = 'oracle/email/extensions/%s/%s';

    const XML_PATH_OBJECT_PATH = 'oracle/email/objects/%s/%s';
    const XML_PATH_REORDER_PATH = 'oracle/email/objects/reorder/%';
    const XML_PATH_CARETIP_PATH = 'oracle/email/objects/caretip/%';
    const XML_PATH_CART_PATH = 'oracle/email/objects/cart/%';
    const XML_PATH_WISHLIST_PATH = 'oracle/email/objects/wishlist/%';
    const XML_PATH_MAPPING_PATH = 'oracle/email/objects/mapping/%';
    const XML_PATH_LOOKUP_PATH = 'oracle/email/objects/reverse/%';
    const FILTER_EVENTS = 'oracle_email_template_filter';

    /**
     * Checks a Magento send for BCC related items
     *
     * @param string $scopeType
     * @param mixed $scopeId
     * @return boolean
     */
    public function isForceMagento($scopeType = 'default', $scopeId = null);

    /**
     * Gets the the cleanup threshold for sent messages
     *
     * @param string $scopeType
     * @param mixed $scopeId
     * @return mixed
     */
    public function getCleanupThreshold($scopeType = 'default', $scopeId = null);

    /**
     * Is Sending allowed to happen through Oracle
     *
     * @param string $messageType
     * @param string $scopeType
     * @param string $scopeId
     * @return boolean
     */
    public function isMessageEnabled($messageType, $scopeType = 'default', $scopeId = null);

    /**
     * Determines if the symbol should be display in the message
     *
     * @param string $messageType
     * @param string $scopeType
     * @param string $scopeId
     * @return boolean
     */
    public function isDisplaySymbol($messageType, $scopeType = 'default', $scopeId = null);

    /**
     * Determines if tax is included with prices
     * 
     * @param string $messageType
     * @param string $scopeType ['default']
     * @param int $scopeId [null]
     * @return boolean
     */
    public function taxIncluded($messageType, $scopeType = 'default', $scopeId = null);

    /**
     * Gets the send flags for any particular send
     *
     * @param string $messageType
     * @param string $scopeType
     * @param string $scopeId
     * @return array
     */
    public function getMessageSendFlags($messageType, $scopeType = 'default', $scopeId = null);

    /**
     * Gets the reply-to for any particular send
     *
     * @param string $messageType
     * @param string $scopeType
     * @param string $scopeId
     * @return string
     */
    public function getMessageReplyTo($messageType, $scopeType = 'default', $scopeId = null);

    /**
     * Gets the message sender to any particular send
     *
     * @param string $messageType
     * @param string $scopeType
     * @param string $scopeId
     * @@return string
     */
    public function getMessageSender($messageType, $scopeType = 'default', $scopeId = null);

    /**
     * Gets the target audience for the message
     *
     * @param string $messageType
     * @param string $scopeType
     * @param mixed $scopeId
     * @return array
     */
    public function getTargetAudience($messageType, $scopeType = 'default', $scopeId = null);

    /**
     * Gets the exclusion lists for the message
     *
     * @param string $messageType
     * @param string $scopeType
     * @param mixed $scopeId
     * @return array
     */
    public function getExclusionLists($messageType, $scopeType = 'default', $scopeId = null);

    /**
     * Gets the Oracle message for the given message type
     *
     * @param string $messageType
     * @param string $messageId
     * @param mixed $store
     * @param boolean $force
     * @return array
     */
    public function getMessage($messageType, $messageId, $store = null, $force = false);

    /**
     * Gets all of the active reminder messages for a scope
     *
     * @param string $messageType
     * @param mixed $store
     * @param boolean $force
     * @return array
     */
    public function getActiveObjects($messageType, $store = null, $force = false);

    /**
     * Returns a tuple of the recorded model type and id
     *
     * @param mixed $model
     * @return array
     */
    public function getModelTuple($model);

    /**
     * Gets all of the potential lookups by templateId
     *
     * @param string $templateId
     * @param string $scopeType
     * @param mixed $scopeId
     * @param boolean $force
     * @return array
     */
    public function getLookup($templateId, $scopeType = 'default', $scopeId = null, $force = false);

    /**
     * Provided the given trigger, get the real model
     *
     * @param TriggerInterface $trigger
     * @return mixed
     */
    public function getTriggerModel(TriggerInterface $trigger);

    /**
     * Provided the model type and id, load the model
     *
     * @param string $modelType
     * @param mixed $modelId
     * @return mixed
     */
    public function loadModel($modelType, $modelId);

    /**
     * Gets the template filter used to parse tags out of messages
     *
     * @return mixed
     */
    public function getTemplateFilter();

    /**
     * Gets the Magento template based on the mapping
     *
     * @param array $templateId
     * @param array $options
     * @return mixed
     */
    public function getTemplate($templateId, $options = []);

    /**
     * Gets the template id on the mapping or default
     *
     * @param array $mapping
     * @param mixed $templateId
     * @return mixed
     */
    public function getTemplateId($mapping, $templateId = null);

    /**
     * Performs an inline replacement of a mapping
     *
     * @param mixed $templateId
     * @param array $options
     * @return array
     */
    public function replaceTemplate($templateId, $options = []);

    /**
     * Provided a collection of object mappings, create reverse lookups
     * and return the parsed messages.
     *
     * @param array $settings
     * @param string $scopeType
     * @param string $scopeId
     * @return void
     */
    public function createLookups($settings, $scopeType = 'default', $scopeId = null);

    /**
     * Provided a collection of settings, create cart and wihslist lokups
     * and saved them in the config table
     *
     * @param array $extension
     * @param string $scopeName
     * @param mixed $scopeId
     * @return void
     */
    public function createReminders($extension, $scopeType = 'default', $scopeId = 'null');

    /**
     * Creates delivery fields from the template associated with the mapping
     *
     * @param mixed $templateId
     * @param array $mapping
     * @param array $options
     * @param array $vars
     * @return array
     */
    public function createDeliveryFields($templateId, $mapping, $options, $vars);

    /**
     * Retrieves extra delivery fields
     *
     * @param array $message
     * @param array $templateVars
     * @param boolean $asContext
     * @return array
     */
    public function getExtraFields($message, $templateVars = [], $asContext = true);
}

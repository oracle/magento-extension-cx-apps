<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Email;

abstract class SettingsAbstract extends \Oracle\M2\Core\Config\ContainerAbstract implements SettingsInterface
{
    protected static $_fallbacks = [
        'caretip' => ['caretip', 'general'],
        'reorder' => ['reorder', 'general'],
        'review' => ['review', 'general'],
        'cart' => ['cart', 'general'],
        'wishlist' => ['wishlist', 'general'],
        'mapping' => ['transactional', 'general']
    ];

    protected static $_typeToPrefix = [
        'caretip' => SettingsInterface::XML_PATH_CARETIP_PATH,
        'reorder' => SettingsInterface::XML_PATH_REORDER_PATH,
        'cart' => SettingsInterface::XML_PATH_CART_PATH,
        'wishlist' => SettingsInterface::XML_PATH_WISHLIST_PATH,
        'mapping' => SettingsInterface::XML_PATH_MAPPING_PATH,
    ];

    protected static $_sendFlags = [
        'authentication' => SettingsInterface::XML_PATH_MESSAGE_AUTHENTICATION,
        'fatigueOverride' => SettingsInterface::XML_PATH_MESSAGE_FATIGUE_OVERRIDE,
        'replyTracking' => SettingsInterface::XML_PATH_MESSAGE_REPLY_TRACKING
    ];

    protected $_data;
    protected $_writer;
    protected $_logger;
    protected $_appEmulation;
    protected $_eventManager;
    protected $_storeManager;
    protected $_connectorSettings;

    /**
     * @param \Oracle\M2\Connector\SettingsInterface $connectorSettings
     * @param \Oracle\M2\Core\Config\FactoryInterface $data
     * @param \Oracle\M2\Core\Config\ScopedInterface $config
     * @param \Oracle\M2\Core\Config\ManagerInterface $writer
     * @param \Oracle\M2\Core\App\EmulationInterface $appEmulation
     * @param \Oracle\M2\Core\Store\ManagerInterface $storeManager
     * @param \Oracle\M2\Core\Event\ManagerInterface $eventManager
     * @param \Oracle\M2\Core\Log\LoggerInterface $logger
     */
    public function __construct(
        \Oracle\M2\Connector\SettingsInterface $connectorSettings,
        \Oracle\M2\Core\Config\FactoryInterface $data,
        \Oracle\M2\Core\Config\ScopedInterface $config,
        \Oracle\M2\Core\Config\ManagerInterface $writer,
        \Oracle\M2\Core\App\EmulationInterface $appEmulation,
        \Oracle\M2\Core\Store\ManagerInterface $storeManager,
        \Oracle\M2\Core\Event\ManagerInterface $eventManager,
        \Oracle\M2\Core\Log\LoggerInterface $logger
    ) {
        parent::__construct($config);
        $this->_connectorSettings = $connectorSettings;
        $this->_data = $data;
        $this->_writer = $writer;
        $this->_appEmulation = $appEmulation;
        $this->_storeManager = $storeManager;
        $this->_eventManager = $eventManager;
        $this->_logger = $logger;
    }

    /**
     * @see parent
     */
    public function isEnabled($scopeType = 'default', $scopeId = null)
    {
        return true;
    }

    /**
     * @see parent
     */
    public function isForceMagento($scopeType = 'default', $scopeId = null)
    {
        return $this->_config->getValue(self::XML_PATH_MAGENTO_BCC, $scopeType, $scopeId) == 'magento';
    }

    /**
     * @see parent
     */
    public function getCleanupThreshold($scopeType = 'default', $scopeId = null)
    {
        return $this->_config->getValue(self::XML_PATH_CLEANUP_THRESHOLD, $scopeType, $scopeId);
    }

    /**
     * @see parent
     */
    public function isMessageEnabled($messageType, $scopeType = 'default', $scopeId = null)
    {
        return (
            !$this->_connectorSettings->isTestMode($scopeType, $scopeId) &&
            $this->_performFallback($messageType, self::XML_PATH_MESSAGE_ENABLED, true, $scopeType, $scopeId)
        );
    }

    /**
     * @see parent
     */
    public function isDisplaySymbol($messageType, $scopeType = 'default', $scopeId = null)
    {
        return $this->_performFallback($messageType, self::XML_PATH_MESSAGE_DISPLAY, true, $scopeType, $scopeId);
    }

    /**
     * @see \Oracle\M2\Email\SettingsInterface::taxIncluded
     * @param string $messageType
     * @param string $scopeType ['default']
     * @param int $scopeId [null]
     * @return boolean
     */
    public function taxIncluded($messageType, $scopeType = 'default', $scopeId = null)
    {
        return $this->_performFallback($messageType, self::XML_PATH_MESSAGE_INCLUDE_TAX, true, $scopeType, $scopeId);
    }

    /**
     * @see parent
     */
    public function isSendingQueued($scopeType = 'default', $scopeId = null)
    {
        return $this->_connectorSettings->isEventQueued($scopeType, $scopeId);
    }

    /**
     * @see parent
     */
    public function getMessageSendFlags($messageType, $scopeType = 'default', $scopeId = null)
    {
        $sendFlags = [];
        foreach (self::$_sendFlags as $flag => $xmlPath) {
            if ($this->_performFallback($messageType, $xmlPath, true, $scopeType, $scopeId)) {
                $sendFlags[] = $flag;
            }
        }
        return $sendFlags;
    }

    /**
     * @see parent
     */
    public function getMessageReplyTo($messageType, $scopeType = 'default', $scopeId = null)
    {
        return $this->_performFallback($messageType, self::XML_PATH_MESSAGE_REPLY_TO, false, $scopeType, $scopeId);
    }

    /**
     * @see parent
     */
    public function getMessageSender($messageType, $scopeType = 'default', $scopeId = null)
    {
        return $this->_performFallback($messageType, self::XML_PATH_MESSAGE_SENDER, false, $scopeType, $scopeId);
    }

    /**
     * @see parent
     */
    public function getTargetAudience($messageType, $scopeType = 'default', $scopeId = null)
    {
        $audience = $this->_performFallback($messageType, self::XML_PATH_MESSAGE_AUDIENCE, false, $scopeType, $scopeId);
        if (empty($audience)) {
            return [];
        } else {
            return explode(',', $audience);
        }
    }

    /**
     * @see parent
     */
    public function getExclusionLists($messageType, $scopeType = 'default', $scopeId = null)
    {
        $lists = $this->_performFallback($messageType, self::XML_PATH_MESSAGE_EXCLUSION_LISTS, false, $scopeType, $scopeId);
        if (empty($lists)) {
            return [];
        } else {
            return explode(',', $lists);
        }
    }

    /**
     * @see parent
     */
    public function getMessage($messageType, $messageId, $store = null, $force = false)
    {
        if ($messageType == 'review') {
            $message = [];
            if ($force || $this->_config->isSetFlag(sprintf(self::XML_PATH_MESSAGE_ENABLED, 'review'), 'store', $store)) {
                $message = $this->_postSettings('review', 'store', $store);
                $message = $this->_fillObject('review', $message, 'store', $store);
            }
            return $message;
        } else {
            $object = $this->_deserializeObject($messageType, $messageId, self::$_typeToPrefix[$messageType], $store, $force);
            if (!empty($object)) {
                if ($messageType == 'mapping') {
                    $object['filters'] = [];
                    $object['isSendingQueued'] = $this->isSendingQueued('store', $store);
                } elseif (in_array($messageType, ['reorder', 'caretip'])) {
                    return array_merge($this->_postSettings($messageType, 'store', $store), $object);
                }
            }
            return $object;
        }
    }

    /**
     * @see parent
     */
    public function getActiveObjects($messageType, $store = null, $force = false)
    {
        return $this->_deserializeObjects($messageType, sprintf(self::XML_PATH_OBJECT_PATH, $messageType, '%'), $store, $force);
    }

    /**
     * @see parent
     */
    public function createLookups($settings, $scopeType = 'default', $scopeId = null)
    {
        $lookupTable = $this->_mappingPaths($settings, $scopeType, $scopeId);
        foreach ($settings as $setting) {
            if ($setting['id'] != 'mapping') {
                continue;
            }
            foreach ($setting['fields'] as $field) {
                $templateId = $field['value']['configurationPath'];
                if (array_key_exists($templateId, $lookupTable)) {
                    $templateId = $lookupTable[$templateId];
                }
                if (array_key_exists('customContext', $field['value'])) {
                    $configuredTemplateId = $field['value']['customContext'];
                    if ($configuredTemplateId != 'defaultContext' && !array_key_exists($configuredTemplateId, $lookupTable)) {
                        $configuredTemplateLookup = str_replace('%', $this->_safeId($configuredTemplateId), self::XML_PATH_LOOKUP_PATH);
                        $this->_writer->save($configuredTemplateLookup, $field['id'], $scopeType, $scopeId);
                    }
                }
                $templateToObject = str_replace('%', $this->_safeId($templateId), self::XML_PATH_LOOKUP_PATH);
                $this->_writer->save($templateToObject, $field['id'], $scopeType, $scopeId);
            }
        }
    }

    /**
     * @see parent
     */
    public function createReminders($extensions, $scopeType = 'default', $scopeId = null)
    {
        foreach ($extensions as $extension) {
            if (preg_match('/([^_]+)_(\d+)/', $extension['id'], $matches)) {
                $object = ['id' => $matches[1] . '_' . $matches[2]];
                $objectId = $this->_safeId($object['id']);
                foreach ($extension['fields'] as $field) {
                    $object[$field['id']] = $field['value'];
                }
                if ((int)$matches[2] > 1) {
                    $object['previousMessage'] = $matches[1] . '_' . ((int)$matches[2] - 1);
                }
                // Only write the lookup if it's enabled
                if (isset($object['enabled']) && $object['enabled']) {
                    $reminderId = sprintf(self::XML_PATH_OBJECT_PATH, $matches[1], $objectId);
                    $this->_writer->save($reminderId, serialize($object), $scopeType, $scopeId);
                }
            }
        }
    }

    /**
     * @see parent
     */
    public function getLookup($templateId, $scopeType = 'default', $scopeId = null, $force = false)
    {
        $fullPath = str_replace('%', $this->_safeId($templateId), self::XML_PATH_LOOKUP_PATH);
        if ($force || $scopeType != 'default') {
            return $this->_config->getValue($fullPath, $scopeType, $scopeId);
        } else {
            $data = $this->_data->getCollection()
                ->addFieldToFilter('path', ['eq' => $fullPath]);
            foreach ($data as $config) {
                return $config->getValue();
            }
            return null;
        }
    }

    /**
     * @see parent
     */
    public function getTemplateId($mapping, $templateId = null)
    {
        if (empty($mapping)) {
            return $templateId;
        }
        if (array_key_exists('customContext', $mapping)) {
            if ($mapping['customContext'] != 'defaultContext') {
                return $mapping['customContext'];
            }
        }
        return $mapping['configurationPath'];
    }

    /**
     * @see parent
     */
    public function replaceTemplate($templateId, $options = [])
    {
        $variableFilter = $this->getTemplateFilter();
        $variableFilter->setReplaceToTags(true);
        $return = $this->_process($variableFilter, $templateId, $options);
        return $return;
    }

    /**
     * @see parent
     */
    public function createDeliveryFields($templateId, $mapping, $options, $vars)
    {
        $variableFilter = $this->getTemplateFilter();
        if (!$mapping['isSendingQueued']) {
            $this->_eventManager->dispatch(self::FILTER_EVENTS, [
                'filter' => $variableFilter,
                'message' => $mapping,
            ]);
        }
        $return = $this->_process($variableFilter, $this->getTemplateId($mapping, $templateId), $options, $vars, $mapping);
        return $return['fields'];
    }

    /**
     * @see parent
     */
    public function getExtraFields($message, $templateVars = [], $asContext = true)
    {
        $variableFilter = $this->getTemplateFilter();
        $variableFilter->setVariables($templateVars);
        $this->_eventManager->dispatch(self::FILTER_EVENTS, [
            'filter' => $variableFilter,
            'message' => $message,
        ]);
        if ($asContext) {
            return $variableFilter->getContext($message);
        } else {
            return $variableFilter->applyAndTransform($message);
        }
    }

    /**
     * Gets the post purchase master settings for a given object
     *
     * @param string $messageType
     * @param string $scopeType
     * @param mixed $scopeId
     * @return array
     */
    protected function _postSettings($messageType, $scopeType = 'default', $scopeId = null)
    {
        $message = [];
        $reviewFields = [
            'triggerStatus' => 'string',
            'cancelStatus' => 'array',
            'sendPeriod' => 'int',
            'exclusionCategories' => 'array'
        ];
        if ($messageType == 'review') {
            $reviewFields['categories'] = 'array';
            $reviewFields['messageId'] = 'string';
            $reviewFields['reviewForm'] = 'string';
        } elseif ($messageType == 'reorder') {
            $reviewFields['multiply'] = 'boolean';
        }
        foreach ($reviewFields as $field => $type) {
            $message[$field] = $this->_config->getValue(
                sprintf(self::XML_PATH_POSTPURCHASE_FIELD, $messageType, $field),
                $scopeType,
                $scopeId
            );
            switch ($type) {
                case 'int':
                    $message[$field] = (int)$message[$field];
                    break;
                case 'boolean':
                    $message[$field] = (boolean)$message[$field];
                    break;
                case 'array':
                    if (empty($message[$field])) {
                        $message[$field] = [];
                    } else {
                        $message[$field] = explode(',', $message[$field]);
                    }
            }
        }
        return $message;
    }

    /**
     * Performs the template filtering on the specified template and context
     *
     * @param mixed $filter
     * @param mixed $templateId
     * @param array $options
     * @param array $vars
     * @return array
     */
    protected function _process($filter, $templateId, $options = [], $vars = [], $mapping = [])
    {
        $defaultOptions = [
            'area' => 'frontend',
            'store' => $this->_storeManager->getStore()->getId()
        ];

        $template = $this->getTemplate($templateId, array_merge($defaultOptions, $options));
        if (empty($options)) {
            $options = array_merge($defaultOptions, $template->getDesignConfig()->getData());
        } else {
            $options = array_merge($defaultOptions, $options);
        }

        $this->_appEmulation->startEnvironmentEmulation($options['store'], $options['area'], true);
        if (isset($vars['subscriber'])) {
            $storeId = $vars['subscriber']->getStoreId();
        } else {
            $storeId = $options['store'];
        }
        $variables = $this->_addEmailVariables($template, $vars, $storeId);
        $variables['this'] = $template;
        $this->_applyFilterFunctions($template, $filter);
        $filter->setVariables($variables);
        $filter->setStoreId($storeId);
        $filter->setDesignParams($options);
        $filter->setChildFilter($this->_applyFilterFunctions($template, $this->getTemplateFilter())
            ->setVariables($variables)
            ->setStoreId($storeId)
            ->setDesignParams($options));
        $return = [];
        try {
            $return['subject'] = $filter->filter($template->getTemplateSubject());
            $return['content'] = $this->_processContent($template, $filter);
            $return['fields'] = empty($mapping) ?
                $filter->getReplacedTags() :
                $filter->applyAndTransform($mapping);
        } catch (\Exception $e) {
            $this->_logger->critical($e);
        }
        $this->_appEmulation->stopEnvironmentEmulation();
        return $return;
    }

    /**
     * Protected method to supply any filter functions on the template
     *
     * @param mixed $template
     * @param mixed $filter
     * @return $filter
     */
    abstract protected function _applyFilterFunctions($template, $filter);

    /**
     * Processed the template content
     *
     * @param mixed $template
     * @param mixed $filter
     * @return string
     */
    protected function _processContent($template, $filter)
    {
        return $filter->filter($template->getTemplateText());
    }

    /**
     * Given a collection of Connector objects, create a reverse
     * lookup by configuration path
     *
     * @param array $objects
     * @param string $scopeType
     * @param mixed $scopeId
     * @return array
     */
    protected function _mappingPaths($objects, $scopeType = 'default', $scopeId = null)
    {
        $lookupTable = $sqls = $ors = [];
        foreach ($objects as $object) {
            if ($object['id'] != 'mapping') {
                continue;
            }
            foreach ($object['fields'] as $field) {
                $parts = explode('_', $field['value']['configurationPath']);
                $lastPart = end($parts);
                $sqls[$lastPart] = ['like' => "%{$lastPart}"];
            }
        }
        if (empty($sqls)) {
            return [];
        }
        foreach ($sqls as $sql) {
            $ors[] = 'path';
        }
        if ($scopeType == 'store' || $scopeType == 'website') {
            $scopeType .= 's';
        }
        $configs = $this->_data->getCollection()
            ->addFieldToFilter($ors, array_values($sqls))
            ->addFieldToFilter('scope', ['eq' => $scopeType]);
        if (!is_null($scopeId)) {
            $configs->addFieldToFilter('scope_id', ['eq' => $scopeId]);
        }
        foreach ($configs as $config) {
            $replacedPath = str_replace('/', '_', $config->getPath());
            $lookupTable[$replacedPath] = $config->getValue();
        }
        return $lookupTable;
    }

    /**
     * Determines if the message is enabled
     *
     * @param array $message
     * @param boolean $force
     * @param string $scope
     * @param mixed $scopeId
     * @return boolean
     */
    protected function _disabledMessage($message, $force, $scope, $scopeId)
    {
        return !$force && (
            isset($message['enabled']) && (
                $message['enabled'] === false ||
                $this->_connectorSettings->isTestMode($scope, $scopeId)
            )
        );
    }

    /**
     * Deserilizes a single object from the config table
     *
     * @param string $messageType
     * @param string $messageId
     * @param string $configPrefix
     * @param mixed $store
     * @param boolean $force
     * @return array
     */
    protected function _deserializeObject($messageType, $messageId, $configPrefix, $store = null, $force)
    {
        $messageId = $this->_safeId($messageId);
        if (is_null($store)) {
            $collection = $this->_data->getCollection()
                ->addFieldToFilter('path', ['eq' => str_replace('%', $messageId, $configPrefix)]);
            $object = null;
            foreach ($collection as $config) {
                $object = unserialize($config->getValue());
                $object['scope'] = $config->getScope();
                $object['scopeId'] = $config->getScopeId();
                break;
            }
            if (empty($object) || $this->_disabledMessage($object, $force, $object['scope'], $object['scopeId'])) {
                return [];
            }
            return $this->_fillObject($messageType, $object, $object['scope'], $object['scopeId']);
        } else {
            $object = $this->_config->getValue(str_replace('%', $messageId, $configPrefix), 'store', $store);
            if (empty($object)) {
                return [];
            }
            $object = unserialize($object);
            if ($this->_disabledMessage($object, $force, 'store', $store)) {
                return [];
            }
            return $this->_fillObject($messageType, $object, 'store', $store);
        }
    }

    /**
     * Deserializes a collection of connector objects representing
     * configured message types at the store scope and higher
     *
     * @param string $messageType
     * @param string $configPrefix
     * @param mixed $store
     * @param boolean $force
     * @return array
     */
    protected function _deserializeObjects($messageType, $configPrefix, $store = null, $force)
    {
        $objects = [];
        if ($store && ($force || $this->_config->isSetFlag(sprintf(self::XML_PATH_MESSAGE_ENABLED, $messageType), 'store', $store))) {
            $postSettings = [];
            if (in_array($messageType, ['review', 'reorder', 'caretip'])) {
                $postSettings = $this->_postSettings($messageType, 'store', $store);
            }
            $specificity = [];
            $configs = $this->_data->getCollection()
                ->addFieldToFilter('path', ['like' => $configPrefix]);
            foreach ($configs as $config) {
                $object = unserialize($config->getValue());
                $object['scope'] = $config->getScope();
                $object['scopeId'] = $config->getScopeId();
                if ($this->_disabledMessage($object, $force, $object['scope'], $object['scopeId']) || $store && !$this->_validScope($config, $store) || !$this->_moreSpecific($config, $specificity)) {
                    continue;
                }
                if (!empty($postSettings)) {
                    $object = array_merge($object, $postSettings);
                }
                $specificity[$config->getPath()] = $config->getScope();
                $objects[$object['id']] = $store ?
                    $this->_fillObject($messageType, $object, 'store', $store) :
                    $this->_fillObject($messageType, $object, $config->getScope(), $config->getScopeId());
            }
        }
        return $objects;
    }

    /**
     * Gets config value but falling back various message type trees
     *
     * @param string $messageType
     * @param string $configPrefix
     * @param boolean $flag
     * @param string $scopeType
     * @param mixed $scopeId
     * @return mixed
     */
    protected function _performFallback($messageType, $configPrefix, $boolean = false, $scopeType = 'default', $scopeId = null)
    {
        $thing = $boolean ? false : '';
        if (array_key_exists($messageType, self::$_fallbacks)) {
            foreach (self::$_fallbacks[$messageType] as $fallback) {
                $configPath = sprintf($configPrefix, $fallback);
                $thing = $this->_config->getValue($configPath, $scopeType, $scopeId);
                if (!is_null($thing) && $thing !== '') {
                    if ($boolean) {
                        $thing = (boolean)$thing;
                    }
                    break;
                }
            }
        }
        return $thing;
    }

    /**
     * Fills the general object information all the up the chain
     *
     * @param string $messageType
     * @param array $object
     * @param string $scopeType
     * @param mixed $scopeId
     * @return array
     */
    protected function _fillObject($messageType, $object, $scopeType = 'default', $scopeId)
    {
        if (!isset($object['replyTo']) || is_null($object['replyTo'])) {
            $object['replyTo'] = $this->getMessageReplyTo($messageType, $scopeType, $scopeId);
        }
        if (!isset($object['sender'])) {
            $object['sender'] = $this->getMessageSender($messageType, $scopeType, $scopeId);
        }
        if (!isset($object['targetAudience'])) {
            $object['targetAudience'] = $this->getTargetAudience($messageType, $scopeType, $scopeId);
        }
        if (!isset($object['exclusionLists'])) {
            $object['exclusionLists'] = $this->getExclusionLists($messageType, $scopeType, $scopeId);
        }
        if (!isset($object['enabled'])) {
            $object['enabled'] = $this->isMessageEnabled($messageType, $scopeType, $scopeId);
        }
        if (!isset($object['displaySymbol'])) {
            $object['displaySymbol'] = $this->isDisplaySymbol($messageType, $scopeType, $scopeId);
        }
        if (!isset($object['includeTax'])) {
            $object['includeTax'] = $this->taxIncluded($messageType, $scopeType, $scopeId);
        }
        $sendFlags = [];
        foreach (self::$_sendFlags as $flag => $xmlPath) {
            if (array_key_exists($flag, $object)) {
                if ($object[$flag]) {
                    $sendFlags[] = $flag;
                }
                continue;
            }
            if ($this->_performFallback($messageType, $xmlPath, true, $scopeType, $scopeId)) {
                $sendFlags[] = $flag;
            }
        }
        if ($object['enabled'] && $messageType == 'mapping') {
            $object['enabled'] = $object['sendType'] != 'magento';
        }
        $object['sendFlags'] = $sendFlags;
        $object['scope'] = $scopeType;
        $object['scopeId'] = is_numeric($scopeId) ? $scopeId : $scopeId->getId();
        return $object;
    }

    /**
     * Hate that I have to do this...
     *
     * @param mixed $template
     * @param array $vars
     * @param array $storeId
     * @return array
     */
    protected function _addEmailVariables($template, $vars, $storeId)
    {
        $class = new \ReflectionClass($template);
        try {
            if ($class->hasMethod($this->_getEmailVariableMethod())) {
                $addEmailVariables = $class->getMethod($this->_getEmailVariableMethod());
                $addEmailVariables->setAccessible(true);
                return $addEmailVariables->invoke($template, $vars, $storeId);
            }
            $vars['logo_url'] = $this->_invokeEmailMethod($template, $class, '_getLogoUrl', $storeId);
            $vars['logo_alt'] = $this->_invokeEmailMethod($template, $class, '_getLogoAlt', $storeId);
        } catch (\ReflectionException $re) {
            $this->_logger->critical($re);
        }
        return $vars;
    }

    /**
     * Gets a string value from a protected method execution
     *
     * @param mixed $template
     * @param \ReflectionClass $class
     * @param string $methodName
     * @param mixed $storeId
     * @return string
     */
    protected function _invokeEmailMethod($template, \ReflectionClass $class, $methodName, $storeId)
    {
        if ($class->hasMethod($methodName)) {
            $method = $class->getMethod($methodName);
            $method->setAccessible(true);
            return $method->invoke($template, $storeId);
        }
        return '';
    }

    /**
     * @return string
     */
    protected function _getEmailVariableMethod()
    {
        return 'addEmailVariables';
    }
}

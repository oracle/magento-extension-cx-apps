<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Core\Config;

abstract class ContainerAbstract
{
    /** @var \Oracle\M2\Core\Config\ScopedInterface */
    protected $_config;

    /**
     * @param \Oracle\M2\Core\Config\ScopedInterface $config
     */
    public function __construct(
        \Oracle\M2\Core\Config\ScopedInterface $config
    ) {
        $this->_config = $config;
    }

    /**
     * Returns an array for the setting
     *
     * @param string $path
     * @param string $scopeType
     * @param mixed $scopeId
     * @return array
     */
    protected function _getArray($path, $scopeType = 'default', $scopeId = null)
    {
        $value = $this->_config->getValue($path, $scopeType, $scopeId);
        if (empty($value)) {
            return [];
        } elseif (is_string($value)) {
            return explode(',', $value);
        }
        return $value;
    }

    /**
     * Is the scope valid for the store's scope path
     *
     * @param mixed $config
     * @param mixed $store
     * @return boolean
     */
    protected function _validScope($config, $store)
    {
        return ($config->getScope() == 'default'
            || ($config->getScope() == 'websites' && $config->getScopeId() == $store->getWebsiteId())
            || ($config->getScope() == 'stores' && $config->getScopeId() == $store->getId()));
    }

    /**
     * Determines specificty for config data
     *
     * @param mixed $config
     * @param array $specificty
     * @return boolean
     */
    protected function _moreSpecific($config, $specificity)
    {
        if (array_key_exists($config->getPath(), $specificity)) {
            list($scope, $value) = $specificity[$config->getPath()];
            if ($scope == 'stores') {
                return false;
            } elseif ($scope == 'default') {
                return true;
            } else {
                return $scope == 'websites' && $config->getScope() == 'stores';
            }
        }
        return true;
    }

    /**
     * Gets an XML safe edition of the objectId
     *
     * @param string $objectId
     * @return string
     */
    protected function _safeId($objectId)
    {
        if (preg_match('/^object_/', $objectId)) {
            return $objectId;
        } else {
            return 'object_' . preg_replace('|[\-\s]|', '', $objectId);
        }
    }
}

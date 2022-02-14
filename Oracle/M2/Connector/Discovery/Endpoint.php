<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Connector\Discovery;

class Endpoint
{
    protected $_information = [];
    protected $_sortOrders = [];
    protected $_incrementStep = 5;

    /**
     * Sets the type of endpoint
     *
     * @param string $type
     * @return $this
     */
    public function withType($type, $data = [])
    {
        $this->_information['type'] = $type;
        $this->_information['typeProperties'] = $data;
        return $this;
    }

    /**
     * Adds a category for a given extension
     *
     * @param array $data
     * @return $this
     */
    public function addCategory(array $data)
    {
        return $this->_addItem('categories', $data);
    }

    /**
     * Adds a extension object to the connector endpoint
     *
     * @param $data
     * @return $this
     */
    public function addExtension(array $data)
    {
        return $this->_addItem('extensions', $data, true);
    }

    /**
     * Adds a job object to the connector endpoint
     *
     * @param array $data
     * @return $this
     */
    public function addJob(array $data)
    {
        return $this->_addItem('jobs', $data, true);
    }

    /**
     * Adds a source object to the connector endpoint
     *
     * @param array $data
     * @return $this
     */
    public function addSource(array $data)
    {
        return $this->_addItem('sources', $data);
    }

    /**
     * Adds a script object to the connector endpoint
     *
     * @param array $data
     * @return $this
     */
    public function addScript(array $data)
    {
        return $this->_addItem('scripts', $data, true);
    }

    /**
     * @param string $extensionId
     * @param string $scope
     * @param \stdClass $extensionData
     * @return Endpoint
     */
    public function addAutoConfigData($extensionId, $scope, \stdClass $extensionData)
    {
        $data = [
            'scopeId' => $scope,
            'extensionId' => $extensionId,
            'extensions' => $extensionData
        ];
        return $this->_information['autoConfig'] = $data;
    }

    /**
     * Adds a field to an existing script
     *
     * @param string $groupId
     * @param array $data
     * @return $this
     */
    public function addFieldToScript($groupId, $data)
    {
        return $this->_addFieldToItem('scripts', $groupId, $data);
    }

    /**
     * Adds a field to an existing extension group
     *
     * @param string $extensionId
     * @param array $data
     * @return $this
     */
    public function addFieldToExtension($extensionId, $data)
    {
        return $this->_addFieldToItem('extensions', $extensionId, $data);
    }

    /**
     * Adds a option to a dropdown for an existing field
     *
     * @param string $groupId
     * @param string $fieldId
     * @param array $data
     * @return $this
     */
    public function addOptionToScript($groupId, $fieldId, $data)
    {
        return $this->_addOptionToField('scripts', $groupId, $fieldId, $data);
    }

    /**
     * Adds a field dependency to an existing extension field
     *
     * @param string $groupId
     * @param string $fieldId
     * @param array $depends
     * @return $this
     */
    public function addDependencyToExtension($groupId, $fieldId, $depends)
    {
        return $this->_addDependencyToField('extensions', $groupId, $fieldId, $depends);
    }

    /**
     * Adds a object type to be created in Connector
     *
     * @param array $data
     * @return $this
     */
    public function addObject(array $data)
    {
        return $this->_addItem('objects', $data, true);
    }

    /**
     * Gets a hash table representing the entire connector endpoint
     *
     * @return array
     */
    public function getInformation()
    {
        return $this->_information;
    }

    /**
     * Internal add an item to the endpoint
     *
     * @param string $keyName
     * @param array $data
     * @param boolean $implicitPosition
     * @return $this
     */
    protected function _addItem($keyName, array $data, $implicitPosition = false)
    {
        if (!array_key_exists($keyName, $this->_information)) {
            $this->_information[$keyName] = [];
        }
        if (array_key_exists('id', $data)) {
            if ($implicitPosition && array_key_exists('fields', $data)) {
                $index = 0;
                foreach ($data['fields'] as &$field) {
                    $index++;
                    if (array_key_exists('position', $field)) {
                        $index = max($index, $field['position']);
                    } else {
                        $field['position'] = $index;
                    }
                }
            }
            $data = ['sort_order' => $this->_increment($keyName), 'definition' => $data];
        }
        $this->_information[$keyName][] = $data;
        return $this;
    }

    /**
     * Internal add a field to an existing group
     *
     * @param string $keyName
     * @param string $groupId
     * @param array $data
     * @return $this
     */
    protected function _addFieldToItem($keyName, $groupId, $data)
    {
        foreach ($this->_information[$keyName] as &$group) {
            $definition =& $group['definition'];
            if ($definition['id'] == $groupId) {
                $fields = $definition['fields'];
                $lastField = end($fields);
                $index = array_key_exists('position', $data) ? $data['position'] : $lastField['position'] + 1;
                $data['position'] = $index;
                $definition['fields'][] = $data;
                return $this;
            }
        }
        return $this;
    }

    /**
     * Internal add an item to a field options
     *
     * @param string $keyName
     * @param string $groupId
     * @param string $fieldId
     * @param array $data
     * @return $this
     */
    protected function _addOptionToField($keyName, $groupId, $fieldId, $data)
    {
        foreach ($this->_information[$keyName] as &$group) {
            $definition =& $group['definition'];
            if ($definition['id'] == $groupId) {
                foreach ($definition['fields'] as &$field) {
                    if ($field['id'] == $fieldId) {
                        if (empty($field['typeProperties']['options'])) {
                            $field['typeProperties']['default'] = $data['id'];
                        }
                        array_push($field['typeProperties']['options'], $data);
                        return $this;
                    }
                }
            }
        }
        return $this;
    }

    /**
     * Internal add a dependency to an existing field
     *
     * @param string $keyName
     * @param string $groupId
     * @param array $data
     * @return $this
     */
    protected function _addDependencyToField($keyName, $groupId, $fieldId, $data)
    {
        foreach ($this->_information[$keyName] as &$group) {
            $definition =& $group['definition'];
            if ($definition['id'] == $groupId) {
                foreach ($definition['fields'] as &$field) {
                    if ($field['id'] == $fieldId) {
                        if (!array_key_exists('depends', $field)) {
                            $field['depends'] = [];
                        }
                        $field['depends'][] = $data;
                        return $this;
                    }
                }
            }
        }
        return $this;
    }

    /**
     * Increment the sort order for any grouping with the endpoint
     *
     * @param string $keyName
     * @return $this
     */
    protected function _increment($keyName)
    {
        if (!array_key_exists($keyName, $this->_sortOrders)) {
            $this->_sortOrders[$keyName] = 0;
        }
        $this->_sortOrders[$keyName] += $this->_incrementStep;
        return $this->_sortOrders[$keyName];
    }
}

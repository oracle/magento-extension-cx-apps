<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Core;

class DataObject extends \Oracle\M2\Common\DataObject
{
    protected $_trackChanges;
    protected $_changes = [];
    protected $_isDeleted = false;

    /**
     * Override to reflect parity with Magento
     */
    public function __construct(array $data = [], $trackChanges = false)
    {
        parent::__construct($data, true);
        $this->_trackChanges = $trackChanges;
    }

    /**
     * Method to reflect parity with core magento objects
     *
     * @param string $field
     * @return mixed
     */
    public function getData($field)
    {
        $option = $this->_safe($field);
        return $option->getOrElse(null);
    }

    /**
     * Override for Magento
     *
     * @param string $field
     * @param mixed $value
     * @return $this
     */
    public function setData($field, $value)
    {
        return $this->_set($field, $value);
    }

    /**
     * Override to support "has changed" fields
     *
     * @see parent
     */
    protected function _set($field, $value)
    {
        if ($this->_trackChanges) {
            $this->_changes[$field] = $this->_safe($field)->getOrElse(null);
        }
        return parent::_set($field, $value);
    }

    /**
     * Method to reflect parity with core magento objects
     *
     * @return boolean
     */
    public function isObjectNew()
    {
        return !$this->hasId();
    }

    /**
     * Gets the original value for tracking changes
     *
     * @param string $field
     * @return mixed
     */
    public function getOrigData($field)
    {
        return $this->_changes[$field];
    }

    /**
     * Determines if this field value has changed
     *
     * @param string $field
     * @return boolean
     */
    public function dataHasChangedFor($field)
    {
        return $this->_changes[$field] != $this->_data[$field];
    }

    /**
     * Gets or sets the isDeleted flag
     *
     * @param boolean $isDeleted
     * @return boolean
     */
    public function isDeleted($isDeleted = null)
    {
        $result = $this->_isDeleted;
        if (!is_null($isDeleted)) {
            $this->_isDeleted = $isDeleted;
        }
        return $result;
    }

    /**
     * Performs a clone of the data within this class
     */
    public function __clone()
    {
        foreach ($this->_data as $key => $datum) {
            if (is_object($datum)) {
                $this->_data[$key] = clone $datum;
            }
        }
    }
}

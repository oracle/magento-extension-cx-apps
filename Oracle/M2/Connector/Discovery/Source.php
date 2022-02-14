<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Connector\Discovery;

class Source
{
    protected $_registration;
    protected $_page = 0;
    protected $_perPage = 20;
    protected $_scopeHash;
    protected $_uniqueId = false;
    protected $_filters = [];
    protected $_results = [];

    /**
     * @param \Oracle\M2\Connector\RegistrationInterface $registration
     */
    public function __construct(\Oracle\M2\Connector\RegistrationInterface $registration)
    {
        $this->_registration = $registration;
    }

    /**
     * Sets the request params from the request
     *
     * @param array $params
     * @return $this
     */
    public function setParams(array $params = [])
    {
        foreach ($params as $key => $values) {
            list($value) = $values;
            switch ($key) {
                case 'page':
                    $this->_page = $value;
                    break;
                case 'perPage':
                    $this->_perPage = $value;
                    break;
                case 'scopeId':
                    $this->_scopeHash = $value;
                    break;
                case 'id':
                    $this->_uniqueId = $value;
                    break;
                default:
                    $this->_filters[$key] = $value;
                    break;
            }
        }
        return $this;
    }

    /**
     * Gets the filters from the request
     *
     * @return array
     */
    public function getFilters()
    {
        return $this->_filters;
    }

    /**
     * Gets the limit param from the request
     *
     * @return int
     */
    public function getLimit()
    {
        return $this->_perPage;
    }

    /**
     * Gets the offset param from the request
     *
     * @return int
     */
    public function getOffset()
    {
        return $this->_page * $this->_perPage;
    }

    /**
     * Retrieves the current page from the request
     *
     * @return int
     */
    public function getCurrentPage()
    {
        return $this->_page;
    }

    /**
     * Sets the computed results provided by the source handler
     *
     * @param array $results
     * @return $this
     */
    public function setResults(array $results = [])
    {
        $this->_results = $results;
        return $this;
    }

    /**
     * Retrieves a collection of results from the source handler
     *
     * @return array
     */
    public function getResults()
    {
        return $this->_results;
    }

    /**
     * Retrieves the source scope hash for the source, if applicable
     *
     * @return string
     */
    public function getScopeHash()
    {
        if (is_null($this->_scopeHash)) {
            return $this->getRegistration()->getScopeHash();
        } else {
            return $this->_scopeHash;
        }
    }

    /**
     * Gets the registration that belongs to the request
     *
     * @return \Oracle\M2\Connector\RegistrationInterface
     */
    public function getRegistration()
    {
        return $this->_registration;
    }

    /**
     * Gets the unique identifier value for the source
     *
     * @return mixed
     */
    public function getId()
    {
        return $this->_uniqueId;
    }
}

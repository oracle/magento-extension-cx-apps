<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Connector\Discovery;

class Execution
{
    private $_object = [];
    private $_results = [];
    private $_registration;

    /**
     * @param \Oracle\M2\Connector\RegistrationInterface $registration
     */
    public function __construct(\Oracle\M2\Connector\RegistrationInterface $registration)
    {
        $this->_registration = $registration;
    }

    /**
     * Replaces this script execution information
     *
     * @param array $object
     * @return $this
     */
    public function setObject($object)
    {
        $this->_object = $object;
        return $this;
    }

    /**
     * Gets this script execution information
     *
     * @return array
     */
    public function getObject()
    {
        return $this->_object;
    }

    /**
     * Sets the execution results. This may differ between
     * job and scripts results
     *
     * @param array $results
     * @return $this
     */
    public function setResults($results)
    {
        $this->_results = $results;
        return $this;
    }

    /**
     * Sets the job progresses from named collections
     *
     * @param array $progress
     * @return $this
     */
    public function setProgress($progress)
    {
        return $this->setResults($this->createProgresses($progress));
    }

    /**
     * Creates the job progresses from named collections
     *
     * @param array $progress
     * @return array
     */
    public function createProgresses($progress)
    {
        $results = [];
        foreach ($progress as $name => $value) {
            $results[] = ['name' => $name, 'value' => $value];
        }
        return $results;
    }

    /**
     * Gets the execution results
     *
     * @return array
     */
    public function getResults()
    {
        return $this->_results;
    }

    /**
     * Gets the registration associated with this script
     *
     * @return \Oracle\M2\Connector\RegistrationInterface
     */
    public function getRegistration()
    {
        return $this->_registration;
    }
}

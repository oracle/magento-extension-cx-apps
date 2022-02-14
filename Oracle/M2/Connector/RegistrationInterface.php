<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Connector;

interface RegistrationInterface
{
    const NAME = 'name';
    const ENVIRONMENT = 'environment';
    const CONNECTOR_KEY = 'connector_key';
    const IS_ACTIVE = 'is_active';
    const SCOPE_NAME = 'scope';
    const SCOPE_ID = 'scope_id';
    const SCOPE_CODE = 'scope_code';
    const IS_PROTECTED = 'is_protected';
    const USERNAME = 'username';
    const PASSWORD = 'password';

    /**
     * @param string $hash
     *
     * @return Oracle\Connector\Model\Registration
     */
    public function setScopeHash($hash);

    /**
     * @param boolean $includeCode
     *
     * @return string
     */
    public function getScopeHash($includeCode = false);

    /**
     * Gets the name of the registration as appears in Connector
     *
     * @return string
     */
    public function getName();

    /**
     * Gets the target environment for Connector
     *
     * @return string
     */
    public function getEnvironment();

    /**
     * Gets the Oracle site hash
     *
     * @return string
     */
    public function getConnectorKey();

    /**
     * Gets the scope are for the scope tree
     *
     * @return string
     */
    public function getScope();

    /**
     * Gets the scope id for the scope area
     *
     * @return int
     */
    public function getScopeId();

    /**
     * Gets the scope code for the scope tree
     *
     * @return string
     */
    public function getScopeCode();

    /**
     * Gets the active flag for a registration
     *
     * @return bool
     */
    public function getIsActive();

    /**
     * Gets the protected flag for a registration
     *
     * @return bool
     */
    public function getIsProtected();

    /**
     * Gets the username used for basic auth
     *
     * @return string
     */
    public function getUsername();

    /**
     * Gets the password used for basic auth
     *
     * @return string
     */
    public function getPassword();

    /**
     * Gets the platform specific suffix for registration
     *
     * @return string
     */
    public function getPlatformSuffix();
}

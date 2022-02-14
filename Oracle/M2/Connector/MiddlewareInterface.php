<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Connector;

interface MiddlewareInterface
{
    /**
     * Performs the registration to the Middleware
     *
     * @param \Oracle\M2\Connector\RegistrationInterface $model
     * @return boolean
     */
    public function register(RegistrationInterface $model);

    /**
     * Performs the deregistration to the Middleware
     *
     * @param \Oracle\M2\Connector\RegistrationInterface $registration
     * @return boolean
     */
    public function deregister(RegistrationInterface $registration);

    /**
     * Syncs the settings from the Middleware
     *
     * @param \Oracle\M2\Connector\RegistrationInterface $model
     * @return boolean
     */
    public function sync(RegistrationInterface $model);

    /**
     * Flushes the event queue with a Middleware job
     *
     * @param \Oracle\M2\Connector\RegistrationInterface $model
     * @return boolean
     */
    public function triggerFlush(RegistrationInterface $model);

    /**
     * Creates the lookup key from a given Registration
     *
     * @param \Oracle\M2\Connector\RegistrationInterface $model
     * @return string
     */
    public function installKey(RegistrationInterface $model);

    /**
     * Gets the default store ID from a scope
     *
     * @param string $scopeType
     * @param mixed $scopeId
     * @return string
     */
    public function defaultStoreId($scopeType = 'default', $scopeId = '0');

    /**
     * Gets all store scopes for a registration
     *
     * @param \Oracle\M2\Connector\RegistrationInterface $model
     * @param boolean $includeSelf
     * @return array
     */
    public function storeScopes(RegistrationInterface $model, $includeSelf = false);
}

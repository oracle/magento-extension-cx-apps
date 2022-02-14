<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Connector;

interface ConnectorInterface
{
    /**
     * Creates a scope tree for the registration
     *
     * @param \Oracle\Connector\Model\RegistrationInterface $model
     * @return array
     */
    public function scopeTree(RegistrationInterface $model);

    /**
     * Creates a discovery object for the registration
     *
     * @param \Oracle\Connector\Model\RegistrationInterface $model
     * @return array
     */
    public function discovery(RegistrationInterface $model);

    /**
     * Creates an endpoint object for the registration and service
     *
     * @param \Oracle\M2\Connector\RegistrationInterface $model
     * @param string $serviceName
     * @return array
     */
    public function endpoint(RegistrationInterface $model, $serviceName);

    /**
     * Performs an immediate script execution for the Middleware
     *
     * @param \Oracle\M2\Connector\RegistrationInterface $model
     * @param array $script
     * @return array
     */
    public function executeScript(RegistrationInterface $model, $script);

    /**
     * Performs an immediate source lookup from the Middleware
     *
     * @param \Oracle\M2\Connector\RegistrationInterface $model
     * @param string $sourceId
     * @param array $params
     * @return array
     */
    public function source(RegistrationInterface $model, $sourceId, $params = []);

    /**
     * Syncs the stored settings from connector
     *
     * @param \Oracle\M2\Connector\RegistrationInterface $model
     * @return array
     */
    public function settings(RegistrationInterface $model);

    /**
     * @param \Oracle\M2\Connector\RegistrationInterface $model
     * @return string
     */
    public function getEik(RegistrationInterface $model);

    /**
     * Sorts and flattens out any settings annotated with a sort_order
     *
     * @param array $settings
     * @return array
     */
    public function sortAndSet(array $settings);
}

<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Connector;

interface RegistrationManagerInterface
{
    /**
     * Gets the registration by scope and scopeId
     *
     * @param string $scope
     * @param int $scopeId
     * @param boolean $fallback If true, falls back to parent scopes to get a registration that exists
     * @return RegistrationInterface
     */
    public function getByScope($scope, $scopeId, $fallback);

    /**
     * Gets all of the registrations on the platform
     *
     * @return \Oracle\Connector\Model\ResourceModel\Registration\Collection
     */
    public function getAll();
}

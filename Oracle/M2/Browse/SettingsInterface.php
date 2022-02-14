<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Browse;

interface SettingsInterface extends \Oracle\M2\Connector\Event\HelperInterface
{
    const XML_PATH_ENABLED = 'oracle/integration/extensions/browse_recovery/enabled';
    const XML_PATH_SITE = 'oracle/integration/extensions/browse_recovery/site';
    const XML_PATH_SEARCH_ENABLED = 'oracle/integration/extensions/browse_recovery/search';

    /**
     * Gets order creates a unique customerId for Browse
     *
     * @return string
     */
    public function getUniqueCustomerId();

    /**
     * @return string
     */
    public function getCustomerEmail();

    /**
     * Creates a context with browse event data
     *
     * @param mixed $browse
     * @param mixed $when
     * @return array
     */
    public function createContext($browse, $when = null);

    /**
     * Gets the browse based site id associated to the frontend
     *
     * @param string $scope
     * @param mixed $scopeId
     * @return string
     */
    public function getSiteId($scope = 'default', $scopeId = null);

    /**
     * Determines if the search event type is enabled
     *
     * @param string $scope
     * @param mixed $scopeId
     * @return boolean
     */
    public function isSearchEnabled($scope = 'default', $scopeId = null);
}

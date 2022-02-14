<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Product;

interface SettingsInterface extends \Oracle\M2\Connector\Event\HelperInterface
{
    const XML_PATH_ENABLED = 'oracle/product/extensions/settings/enabled';
    const XML_PATH_ADD_LINK = 'oracle/product/extensions/settings/addProductsLink';
    const XML_PATH_SCOPES = 'oracle/product/extensions/scopes/%';
    const XML_PATH_DEFAULTS = 'oracle/product/extensions/default_fields';
    const XML_PATH_CUSTOMS = 'oracle/product/objects/custom_fields';

    /**
     * Determines if products are configured to be added via URL
     *
     * @param string $scope
     * @param mixed $scopeId
     * @return boolean
     */
    public function isProductAddLink($scope = 'default', $scopeId = null);

    /**
     * Gets the object mapping for the provided scope
     *
     * @param mixed $product
     * @return array
     */
    public function getFieldMapping($product);

    /**
     * Gets the display options for the default mappings
     *
     * @param \Oracle\M2\Connector\RegistrationInterface $registration
     * @return array
     */
    public function getDefaultFields(\Oracle\M2\Connector\RegistrationInterface $registration);

    /**
     * Gets the display options for the custom mappings
     *
     * @param \Oracle\M2\Connector\RegistrationInterface $registration
     * @return array
     */
    public function getCustomFields(\Oracle\M2\Connector\RegistrationInterface $registration);

    /**
     * Gets a list of attributes for Catalog updating
     *
     * @param \Oracle\M2\Connector\RegistrationInterface $registration
     * @return array
     */
    public function getFieldAttributes(\Oracle\M2\Connector\RegistrationInterface $registration);

    /**
     * Gets all of the configured mappings by scope heirarchy
     *
     * @param mixed $scopeId
     * @return array
     */
    public function getAll($storeId = null);

    /**
     * Gets all of the enabled scopes from the settings
     *
     * @param string $scope
     * @param mixed $scopeId
     * @return array
     */
    public function getEnabledStores($scope = 'default', $scopeId = null);
}

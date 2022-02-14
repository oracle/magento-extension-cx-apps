<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Product;

interface CategorySettingsInterface
{
    const XML_PATH_ENCAPSULATION = 'oracle/product/extensions/settings/categoryBranchDelimiter';
    const XML_PATH_FORMAT = 'oracle/product/extensions/settings/categoryFormat';
    const XML_PATH_DELIMITER = 'oracle/product/extensions/settings/categoryDelimiter';
    const XML_PATH_SPECIFICITY = 'oracle/product/extensions/settings/categorySpecificity';
    const XML_PATH_BROADNESS = 'oracle/product/extensions/settings/categoryBroadness';

    /**
     * @param string $scope
     * @param mixed $scopeId
     * @return string
     */
    public function getCategoryEncapsulation($scope = 'default', $scopeId = null);

    /**
     * @param string $scope
     * @param mixed $scopeId
     * @return string
     */
    public function getCategoryDelimiter($scope = 'default', $scopeId = null);

    /**
     * @param string $scope
     * @param mixed $scopeId
     * @return string
     */
    public function getCategoryFormat($scope = 'default', $scopeId = null);

    /**
     * @param string $scope
     * @param mixed $scopeId
     * @return string
     */
    public function getCategorySpecificity($scope = 'default', $scopeId = null);

    /**
     * @param string $scope
     * @param mixed $scopeId
     * @return string
     */
    public function getCategoryBroadness($scope = 'default', $scopeId = null);
}

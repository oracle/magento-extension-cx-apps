<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Product;

class CategorySettings extends \Oracle\M2\Core\Config\ContainerAbstract implements CategorySettingsInterface
{
    /**
     * @see parent
     */
    public function getCategoryDelimiter($scope = 'default', $scopeId = null)
    {
        $delimiter = $this->_config->getValue(self::XML_PATH_DELIMITER, $scope, $scopeId);
        if ($delimiter == 'space') {
            return ' ';
        } else {
            $format = $this->getCategoryFormat($scope, $scopeId);
            return $delimiter . ($format == 'name' ? ' ' : '');
        }
    }

    /**
     * @see parent
     */
    public function getCategoryEncapsulation($scope = 'default', $scopeId = null)
    {
        return $this->_config->getValue(self::XML_PATH_ENCAPSULATION, $scope, $scopeId);
    }

    /**
     * @see parent
     */
    public function getCategoryFormat($scope = 'default', $scopeId = null)
    {
        return $this->_config->getValue(self::XML_PATH_FORMAT, $scope, $scopeId);
    }

    /**
     * @see parent
     */
    public function getCategorySpecificity($scope = 'default', $scopeId = null)
    {
        return $this->_config->getValue(self::XML_PATH_SPECIFICITY, $scope, $scopeId);
    }

    /**
     * @see parent
     */
    public function getCategoryBroadness($scope = 'default', $scopeId = null)
    {
        return $this->_config->getValue(self::XML_PATH_BROADNESS, $scope, $scopeId);
    }
}

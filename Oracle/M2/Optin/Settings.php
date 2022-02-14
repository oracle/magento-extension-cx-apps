<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Optin;

class Settings extends \Oracle\M2\Core\Config\ContainerAbstract implements SettingsInterface
{
    /**
     * @see parent
     */
    public function isEnabled($scopeType = 'default', $scopeId = null)
    {
        return $this->_config->isSetFlag(self::XML_PATH_ENABLED, $scopeType, $scopeId);
    }

    /**
     * @see parent
     */
    public function isSyncUnsub($scopeType = 'default', $scopeId = null)
    {
         return $this->_config->isSetFlag(self::XML_PATH_SYNC_UNSUBS, $scopeType, $scopeId);
    }

    /**
     * @see parent
     */
    public function getAddToListIds($scopeType = 'default', $scopeId = null)
    {
        return $this->_getArray(self::XML_PATH_LISTS, $scopeType, $scopeId);
    }

    /**
     * @see parent
     */
    public function getRemoveFromListIds($scopeType = 'default', $scopeId = null)
    {
        return $this->_getArray(self::XML_PATH_REMOVE_LISTS, $scopeType, $scopeId);
    }

    /**
     * @see parent
     */
    public function isFormEnabled($scopeType = 'default', $scopeId = null)
    {
        return $this->_config->isSetFlag(self::XML_PATH_FORM_ENABLED, $scopeType, $scopeId);
    }

    /**
     * @see parent
     */
    public function getWebformUrl($email, $scopeType = 'default', $scopeId = null)
    {
        $secret = $this->_config->getValue(self::XML_PATH_FORM_SECRET, $scopeType, $scopeId);
        $lookup = $this->_config->getValue(self::XML_PATH_FORM_WEBFORM, $scopeType, $scopeId);
        $parts = explode('/', $lookup);
        $significantParts = array_slice($parts, count($parts) - 6);
        $siteId = $significantParts[2];
        $webformId = $significantParts[1];
        $validationHash = hash_hmac('sha256', $siteId . $webformId . $email, $secret);
        return str_replace(
            [self::CONTACT_TAG, self::VALIDATION_HASH],
            [$email, $validationHash],
            $lookup
        );
    }

    /**
     * @see parent
     */
    public function getWebformHeight($scopeType = 'default', $scopeId = null)
    {
        return $this->_config->getValue(self::XML_PATH_FORM_WEBFORM_HEIGHT, $scopeType, $scopeId);
    }

    /**
     * @see parent
     */
    public function isCheckoutEnabled($scopeType = 'default', $scopeId = null)
    {
        return $this->_config->isSetFlag(self::XML_PATH_CHECKOUT_ENABLED, $scopeType, $scopeId);
    }

    /**
     * @see parent
     */
    public function getCheckoutSource($scopeType = 'default', $scopeId = null)
    {
        return $this->_config->getValue(self::XML_PATH_CHECKOUT_SOURCE, $scopeType, $scopeId);
    }

    /**
     * @see parent
     */
    public function getCheckoutLayout($scopeType = 'default', $scopeId = null)
    {
        return $this->_config->getValue(self::XML_PATH_CHECKOUT_LAYOUT, $scopeType, $scopeId);
    }

    /**
     * @see parent
     */
    public function getCheckoutLabel($scopeType = 'default', $scopeId = null)
    {
        return $this->_config->getValue(self::XML_PATH_CHECKOUT_LABEL, $scopeType, $scopeId);
    }

    /**
     * @see parent
     */
    public function isCheckedByDefault($scopeType = 'default', $scopeId = null)
    {
        return $this->_config->isSetFlag(self::XML_PATH_CHECKOUT_CHECKED, $scopeType, $scopeId);
    }
}

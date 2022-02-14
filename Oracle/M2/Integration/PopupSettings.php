<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Integration;

class PopupSettings extends \Oracle\M2\Core\Config\ContainerAbstract implements PopupSettingsInterface
{
    /**
     * @see parent
     */
    public function isPopupEnabled($scopeType = 'default', $scopeId = null)
    {
        return $this->_config->isSetFlag(self::XML_PATH_POPUP_ENABLED, $scopeType, $scopeId);
    }

    /**
     * @see parent
     */
    public function getPopupDomains($scopeType = 'default', $scopeId = null)
    {
        $domains = $this->_config->getValue(self::XML_PATH_POPUP_DOMAINS, $scopeType, $scopeId);
        if (is_array($domains)) {
            return $domains;
        }
        return explode(',', $domains);
    }

    /**
     * @see parent
     */
    public function isCreateSubscribers($scopeType = 'default', $scopeId = null)
    {
        return $this->_config->isSetFlag(self::XML_PATH_POPUP_CREATE, $scopeType, $scopeId);
    }
}

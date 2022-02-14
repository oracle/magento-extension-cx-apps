<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Integration;

interface PopupSettingsInterface
{
    const XML_PATH_POPUP_ENABLED = 'oracle/integration/extensions/popup_manager/enabled';
    const XML_PATH_POPUP_DOMAINS = 'oracle/integration/extensions/popup_manager/popups';
    const XML_PATH_POPUP_CREATE = 'oracle/integration/extensions/popup_manager/createSubscribers';

    /**
     * Is Popup module enabled?
     *
     * @param string $scopeType
     * @param mixed $scopeId
     * @return boolean
     */
    public function isPopupEnabled($scopeType = 'default', $scopeId = null);

    /**
     * Gets configured popup domains for module
     *
     * @param string $scopeType
     * @param mixed $scopeId
     * @return array
     */
    public function getPopupDomains($scopeType = 'default', $scopeId = null);

    /**
     * Can Pop-up submissions create Magento subscribers
     *
     * @param string $scopeType
     * @param mixed $scopeId
     * @return boolean
     */
    public function isCreateSubscribers($scopeType = 'default', $scopeId = null);
}

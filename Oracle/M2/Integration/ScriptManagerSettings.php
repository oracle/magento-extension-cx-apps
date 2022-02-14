<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Integration;

class ScriptManagerSettings extends \Oracle\M2\Core\Config\ContainerAbstract implements ScriptManagerSettingsInterface
{
    /**
     * @param string $scopeType
     * @param int $scopeId
     * @return bool
     */
    public function isEnabled($scopeType = 'default', $scopeId = null)
    {
        return $this->_config->isSetFlag(self::XML_PATH_SCRIPT_ENABLED, $scopeType, $scopeId);
    }
}

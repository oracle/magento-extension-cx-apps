<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Integration;


interface ScriptManagerSettingsInterface
{
    const XML_PATH_SCRIPT_ENABLED = 'oracle/integration/extensions/script_manager/enabled';

    const SNIPPET_ENDPOINT_PREFIX = "https://cdn.oracle.com/bsm-snippet/";
// oracle/integration/extensions/script_manager/enabled : 1
    /**
     * @param string $scopeType
     * @param int $scopeId
     * @return bool
     */
    public function isEnabled($scopeType = 'default', $scopeId = null);
}

<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Core\Config;

interface ScopedInterface
{
    /**
     * Gets a value store in the system config for a scope
     *
     * @param string $path
     * @param string $scopeName
     * @param mixed $scopeId
     * @return string
     */
    public function getValue($path, $scopeName = 'default', $scopeId = null);

    /**
     * Gets a stored flag in the system config for a scope
     *
     * @param string $path
     * @param string $scopeName
     * @param mixed $scopeId
     * @return boolean
     */
    public function isSetFlag($path, $scopeName = 'default', $scopeId = null);
}

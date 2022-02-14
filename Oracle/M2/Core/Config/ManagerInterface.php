<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Core\Config;

interface ManagerInterface
{
    /**
     * Saves a single entry on the platform
     *
     * @param string $path
     * @param mixed $value
     * @param string $scopeName
     * @param mixed $scopeId
     * @return void
     */
    public function save($path, $value, $scopeName, $scopeId);

    /**
     * Reinits the config cache
     *
     * @return void
     */
    public function reinit();

    /**
     * Deletes all of the stored settings by scope parent and path prefix
     *
     * @param string $path
     * @param string $scopeName
     * @param mixed $scopeId
     * @return void
     */
    public function deleteAll($path, $scopeName, $scopeId);
}

<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Core\App;

interface EmulationInterface
{
    /**
     * Starts an emulated environment for the store in the area
     *
     * @param mixed $storeId
     * @param string $area
     * @param boolean $force
     * @return void
     */
    public function startEnvironmentEmulation($storeId, $area, $force);

    /**
     * Stops any running emulated environment
     *
     * @return void
     */
    public function stopEnvironmentEmulation();
}

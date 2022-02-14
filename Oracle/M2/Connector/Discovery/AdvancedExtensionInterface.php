<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Connector\Discovery;

interface AdvancedExtensionInterface extends ExtensionInterface
{
    /**
     * Observe the discovery builder advanced endpoint to
     * enhance Advanced options
     *
     * @param mixed $observer
     * @return void
     */
    public function advancedAdditional($observer);
}

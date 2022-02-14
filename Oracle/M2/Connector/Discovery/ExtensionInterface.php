<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Connector\Discovery;

interface ExtensionInterface extends TranslationInterface
{
    /**
     * Observe the discovery builder to create extension definition
     *
     * @param mixed $observer
     * @return void
     */
    public function gatherEndpoints($observer);

    /**
     * Observe the endpoint callback to create endpoint definition
     *
     * @param mixed $observer
     * @return void
     */
    public function endpointInfo($observer);
}

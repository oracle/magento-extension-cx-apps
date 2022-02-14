<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Connector\Discovery;

interface TransformEventInterface
{
    /**
     * This extension will transform queued events into Sarlacc data
     *
     * @param mixed $observer
     * @return void
     */
    public function transformEvent($observer);
}

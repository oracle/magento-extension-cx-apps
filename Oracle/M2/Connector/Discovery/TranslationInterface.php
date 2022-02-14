<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Connector\Discovery;

interface TranslationInterface
{
    /**
     * Performs a translation on any string
     *
     * @param string $string
     * @return string
     */
    public function translate($string);
}

<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Core\Store;

interface UrlManagerInterface
{
    /**
     * Creates a frontend url path for the given store
     *
     * @param mixed $store
     * @param string $path
     * @param array $params
     * @return string
     */
    public function getFrontendUrl($store, $path, $params = []);
}

<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Core\Catalog;

interface ProductAttributeCacheInterface
{
    /**
     * Returns a collection of attributes that have labels
     *
     * @return mixed
     */
    public function getOptionArray();

    /**
     * Returns raw collection of attributes objects
     *
     * @return mixed
     */
    public function getCollection();
}

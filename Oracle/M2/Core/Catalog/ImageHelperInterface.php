<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Core\Catalog;

interface ImageHelperInterface
{
    /**
     * Attempts to initializes the product image
     *
     * @param mixed product
     * @param string $attribute
     * @return $this
     */
    public function getImageUrl($product, $attribute);

    /**
     * Attempts to get the default placeholder image
     *
     * @return string
     */
    public function getDefaultPlaceHolderUrl();
}

<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Core\Catalog;

interface ProductCategoryResolverInterface
{
    /**
     * Gets the display information on a product category
     *
     * @param mixed $product
     * @param string $resolver
     * @return string
     */
    public function getCategory($product, $resolver = 'single');
}

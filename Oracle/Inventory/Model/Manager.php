<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Inventory\Model;

class Manager extends \Oracle\M2\Impl\Core\Stock implements \Oracle\M2\Product\CatalogMapperManagerInterface
{
    /**
     * @see parent
     */
    public function getByProduct($product)
    {
        return $this->getByProductId($product->getId(), $product->getStoreId());
    }
}

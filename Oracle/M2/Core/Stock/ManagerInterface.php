<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Core\Stock;

interface ManagerInterface
{
    /**
     * Gets a stock item entry from a product or product Id
     *
     * @param mixed $productId
     * @param mixed $storeId
     */
    public function getByProductId($productId, $storeId = null);
}

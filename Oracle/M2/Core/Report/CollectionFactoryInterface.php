<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Core\Report;

interface CollectionFactoryInterface
{
    /**
     * Returns a collection representing most viewed products
     *
     * @return mixed
     */
    public function getMostViewed();

    /**
     * Returns a collection representing best selling products
     *
     * @return mixed
     */
    public function getBestSellers();

    /**
     * Returns a collection representing new products
     *
     * @return mixed
     */
    public function getNewProducts();
}

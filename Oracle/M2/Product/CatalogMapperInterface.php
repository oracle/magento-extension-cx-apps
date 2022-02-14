<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Product;

interface CatalogMapperInterface
{
    /**
     * Gets any external values from the product
     *
     * @param mixed $observer
     * @return void
     */
    public function setExtraFields($observer);

    /**
     * Sets any external fields for Connector
     *
     * @param mixed $observer
     * @return void
     */
    public function setDefaultFields($observer);

    /**
     * Sets and external custom fields for Connector
     *
     * @param mixed $observer
     * @return void
     */
    public function setCustomFields($observer);

    /**
     * Sets/appends a collection of attributes
     *
     * @param mixed $observer
     * @return void
     */
    public function setFieldAttributes($observer);
}

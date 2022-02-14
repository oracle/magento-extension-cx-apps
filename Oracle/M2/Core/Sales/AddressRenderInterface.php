<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Core\Sales;

interface AddressRenderInterface
{
    /**
     * Takes an address model and formats to the specified type
     *
     * @param mixed $address
     * @param string $type
     * @return string
     */
    public function format($address, $type);
}

<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Integration;

interface SettingsInterface extends CartSettingsInterface, PopupSettingsInterface, CouponSettingsInterface
{
    /**
     * Creates a random guid
     *
     * @return string
     */
    public function generateUUID();
}

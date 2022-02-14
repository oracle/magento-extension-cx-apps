<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Core;

interface MetaInterface
{
    const PLATFORM_ID = 'magento';

    /**
     * Gets the platform name
     *
     * @return string
     */
    public function getName();

    /**
     * Gets the platform version
     *
     * @return string
     */
    public function getVersion();

    /**
     * Gets the platform edition
     *
     * @return string
     */
    public function getEdition();

    /**
     * Gets the extension version
     *
     * @return string
     */
    public function getExtensionVersion();

    /**
     * Gets the admin frontend Name
     *
     * @return string
     */
    public function getAdminFrontName();
}

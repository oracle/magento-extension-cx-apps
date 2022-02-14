<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Core\Store;

interface ManagerInterface
{
    const SCOPE_TYPE_DEFAULT = 'default';
    const SCOPE_TYPE_WEBSITE = 'website';
    const SCOPE_TYPE_STORE = 'store';

    const SCOPE_HASH_DEFAULT = 'default.0';

    /**
     * Gets a specific store
     *
     * @param null|string|bool|int|\Magento\Store\Api\Data\StoreInterface $storeId
     * @return \Magento\Store\Api\Data\StoreInterface
     */
    public function getStore($storeId = null);

    /**
     * Gets the default store view
     *
     * @return mixed
     */
    public function getDefaultStoreView();

    /**
     * Gets all of the websites in the install
     *
     * @return array
     */
    public function getStores();

    /**
     * Gets a specific website
     *
     * @param int $websiteId
     * @return \Magento\Store\Api\Data\WebsiteInterface
     */
    public function getWebsite($websiteId = null);

    /**
     * Gets all of the websites in the install
     *
     * @return array
     */
    public function getWebsites();

    /**
     * @return void
     */
    public function reinitStores();
}

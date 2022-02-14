<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Impl\Core;

class Store implements \Oracle\M2\Core\Store\ManagerInterface
{
    /** @var \Magento\Store\Model\StoreManagerInterface|\Manager\Store\Model\StoreManagerInterface  */
    private $_stores;

    /**
     * @param \Manager\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->_stores = $storeManager;
    }

    /**
     * @see \Oracle\M2\Core\Store\ManagerInterface::getStore
     */
    public function getStore($storeId = null)
    {
        return $this->_stores->getStore($storeId);
    }

    /**
     * @see parent
     * @return \Magento\Store\Api\Data\WebsiteInterface
     */
    public function getDefaultStoreView()
    {
        return $this->_stores->getDefaultStoreView();
    }

    /**
     * @see parent
     */
    public function getStores()
    {
        return $this->_stores->getStores();
    }

    /**
     * @see parent
     * @return \Magento\Store\Api\Data\WebsiteInterface
     */
    public function getWebsite($websiteId = null)
    {
        return $this->_stores->getWebsite($websiteId);
    }

    /**
     * @see parent
     */
    public function getWebsites()
    {
        return $this->_stores->getWebsites();
    }

    /**
     * @see parent
     */
    public function reinitStores()
    {
        return $this->_stores->reinitStores();
    }
}

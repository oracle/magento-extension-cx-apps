<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Connector\Impl\Connector;

use Oracle\Connector\Model\Registration;
use Oracle\Connector\Model\RegistrationFactory;
use Oracle\Connector\Model\ResourceModel\Registration\CollectionFactory;
use Oracle\M2\Connector\RegistrationManagerInterface;
use Magento\Store\Model\StoreManagerInterface;

class RegistrationManager implements \Oracle\M2\Connector\RegistrationManagerInterface
{
    protected $_registrationFactory;
    protected $_collectionFactory;

    /** @var StoreManagerInterface */
    protected $storeManager;

    /**
     * @param CollectionFactory $collectionFactory
     * @param RegistrationFactory $registrationFactory
     * @param StoreManagerInterface $scopeManager
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        RegistrationFactory $registrationFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->_registrationFactory = $registrationFactory;
        $this->_collectionFactory = $collectionFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * @see RegistrationManagerInterface::getByScope
     */
    public function getByScope($scope, $scopeId, $fallback = false)
    {
        if ($fallback) {
            return $this->resolveRegisteredScope($scope, $scopeId);
        } else {
            $registration = $this->_registrationFactory->create()->loadByScope($scope, $scopeId);
            return $registration->getId() ? $registration : null;
        }
    }

    /**
     * @see parent
     */
    public function getAll()
    {
        return $this->_collectionFactory->create()->addActiveFilter();
    }

    /**
     * Return the registration for a scope. Falls back to parent scopes until it finds one.
     *
     * @param string $scope
     * @param int $scopeId
     * @returns Registration
     */
    private function resolveRegisteredScope($scope, $scopeId)
    {
        $store = null;
        switch ($scope) {
            case "store":
                $store = $this->storeManager->getStore($scopeId);
                $registration = $this->_registrationFactory->create()->loadByScope("store", $store->getId());
                if ($registration->getId()) {
                    return $registration;
                }
            // Intentional fallthrough
            case "website":
                $websiteId = $store ? $store->getWebsiteId() : $scopeId;
                $website = $this->storeManager->getWebsite($websiteId);
                $registration = $this->_registrationFactory->create()->loadByScope("website", $website->getId());
                if ($registration->getId()) {
                    return $registration;
                }
            // Intentional fallthrough
            default:
                $registration = $this->_registrationFactory->create()->loadByScope("default", 0);
                return $registration->getId() ? $registration : null;
        }
    }
}

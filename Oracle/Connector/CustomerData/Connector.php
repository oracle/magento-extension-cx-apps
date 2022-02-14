<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Connector\CustomerData;

use Oracle\M2\Connector\RegistrationInterface;
use Oracle\M2\Core\Store\ManagerInterface;
use Oracle\M2\Notification\Manager;
use Magento\Customer\CustomerData\SectionSourceInterface;

class Connector implements SectionSourceInterface
{
    /**
     * @var \Oracle\M2\Connector\SettingsInterface $settings
     */
    protected $settings;

    /**
     * @var \Oracle\M2\Connector\RegistrationManager $registrationManager
     */
    protected $registrationManager;

    /**
     * @var \Oracle\M2\Core\Store\ManagerInterface $storeManager
     */
    protected $storeManager;

    /**
     * @var \Oracle\Connector\Model\ResourceModel\Registration\Collection $allRegistrations
     */
    protected $allRegistrations;

    /** @var \Oracle\M2\Core\Log\LoggerInterface  */
    protected $logger;

    /**
     * @param \Oracle\M2\Connector\SettingsInterface $settings
     * @param \Oracle\M2\Connector\RegistrationManagerInterface $registrationManager
     * @param \Oracle\M2\Core\Store\ManagerInterface $storeManager
     * @param \Oracle\M2\Core\Log\LoggerInterface $logger
     */
    public function __construct(
        \Oracle\M2\Connector\SettingsInterface $settings,
        \Oracle\M2\Connector\RegistrationManagerInterface $registrationManager,
        \Oracle\M2\Core\Store\ManagerInterface $storeManager,
        \Oracle\M2\Core\Log\LoggerInterface $logger
    ) {
        $this->settings = $settings;
        $this->registrationManager = $registrationManager;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
    }

    /**
     * @see SectionSourceInterface::getSectionData
     * @return array
     */
    public function getSectionData()
    {
        $registration = $this->getRegistration();
        return [
            'eik' => $registration ? $this->settings->getEik($registration->getScope(), $registration->getScopeId()) : null,
            'serviceUrl' => $this->settings->getServiceUrl(),
            'scope' => $registration ? $this->getScopeHash($registration) : null,
            'instanceType' => $registration ? $registration->getEnvironment() : null
        ];
    }

    /**
     * Attempt to get a registration at the store level first, default level last
     *
     * @returns \Oracle\M2\Connector\RegistrationInterface|null
     */
    private function getRegistration()
    {
        $store = $this->storeManager->getStore();
        $website = $this->storeManager->getWebsite();
        $registration = $this->getRegistrationByScopeHash($this->getScopeHash($store));
        if (!$registration) {
            $registration = $this->getRegistrationByScopeHash($this->getScopeHash($website));
        }
        if (!$registration) {
            $registration = $this->getRegistrationByScopeHash(ManagerInterface::SCOPE_HASH_DEFAULT);
        }
        if (!$registration) {
            $this->logger->debug('Failed to load registration for store ' . $store->getId() . ', website ' . $website->getId());
        }

        return $registration;
    }

    /**
     * @param string $scopeHash
     * @return \Oracle\M2\Connector\RegistrationInterface|null
     */
    private function getRegistrationByScopeHash($scopeHash)
    {
        $allRegistrations = $this->getAllRegistrations();
        $registration = null;
        foreach ($allRegistrations as $potentialRegistration) {
            if ($scopeHash == $potentialRegistration->getScopeHash()) {
                $registration = $potentialRegistration;
                break;
            }
        }
        return $registration;
    }

    /**
     * @return \Oracle\Connector\Model\ResourceModel\Registration\Collection
     */
    private function getAllRegistrations()
    {
        if ($this->allRegistrations === null) {
            $this->allRegistrations = $this->registrationManager->getAll();
        }

        return $this->allRegistrations;
    }

    /**
     * @param \Magento\Store\Api\Data\StoreInterface
     *  |\Magento\Store\Api\Data\Website
     *  |\Oracle\M2\Connector\RegistrationInterface
     * $entity
     *
     * @throws \InvalidArgumentException
     */
    private function getScopeHash($entity)
    {
        $scopeParts = [];
        if ($entity instanceof \Magento\Store\Api\Data\WebsiteInterface) {
            $scopeParts = [ManagerInterface::SCOPE_TYPE_WEBSITE, $entity->getId()];
        } elseif ($entity instanceof \Magento\Store\Api\Data\StoreInterface) {
            $scopeParts = [ManagerInterface::SCOPE_TYPE_STORE, $entity->getId()];
        } elseif ($entity instanceof RegistrationInterface) {
            $scopeParts = [ManagerInterface::SCOPE_TYPE_DEFAULT, 0];
            if ($entity->getScope() != ManagerInterface::SCOPE_TYPE_DEFAULT) {
                $scopeParts = [$entity->getScope(), $entity->getScopeId()];
            }
        }

        if (empty($scopeParts)) {
            $message = '$entity must be of type \Magento\Store\Api\Data\WebsiteInterface, '
                . '\Magento\Store\Api\Data\StoreInterface, or \Oracle\M2\Connector\RegistrationInterface';
            throw new \InvalidArgumentException($message);
        }

        return implode('.', $scopeParts);
    }
}

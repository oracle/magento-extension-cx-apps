<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Notification;

abstract class ExtensionAbstract implements \Oracle\M2\Connector\Discovery\AdvancedExtensionInterface
{
    /** @var \Oracle\M2\Notification\SettingsInterface */
    protected $_settings;

    /** @var \Oracle\M2\Notification\ManagerInterface */
    protected $_manager;

    /** @var \Oracle\M2\Connector\SettingsInterface */
    protected $_connectorSettings;

    /**
     * @param \Oracle\M2\Connector\SettingsInterface $connectorSettings
     * @param \Oracle\M2\Notification\SettingsInterface $settings
     * @param \Oracle\M2\Notification\ManagerInterface $manager
     */
    public function __construct(
        \Oracle\M2\Connector\SettingsInterface $connectorSettings,
        \Oracle\M2\Notification\SettingsInterface $settings,
        \Oracle\M2\Notification\ManagerInterface $manager
    ) {
        $this->_settings = $settings;
        $this->_manager = $manager;
        $this->_connectorSettings = $connectorSettings;
    }

    /**
     * @see parent
     */
    public function getSortOrder()
    {
        return 5;
    }

    /**
     * @see parent
     */
    public function getEndpointId()
    {
        return 'notification';
    }

    /**
     * @see parent
     */
    public function getEndpointName()
    {
        return 'Notifications';
    }

    /**
     * @see parent
     */
    public function getEndpointIcon()
    {
        return 'mage-icon-messages';
    }

    /**
     * @see parent
     */
    public function gatherEndpoints($observer)
    {
        $registration = $observer->getRegistration();
        if ($this->_connectorSettings->isToggled($this->getEndpointId(), $registration->getScope(), $registration->getScopeId())) {
            $observer->getDiscovery()->addGroupHelper($this);
        }
    }

    /**
     * @see parent
     */
    public function endpointInfo($observer)
    {
        $observer->getEndpoint()->addExtension([
            'id' => 'settings',
            'name' => 'Settings',
            'fields' => [
                [
                    'id' => 'notification_enabled',
                    'name' => 'Notification Enabled',
                    'type' => 'boolean',
                    'typeProperties' => [ 'default' => false ]
                ],
                [
                    'id' => 'notification_email',
                    'name' => 'Notification Email',
                    'type' => 'text',
                    'required' => true,
                    'depends' => [
                        [ 'id' => 'notification_enabled', 'values' => [ true ] ]
                    ]
                ]
            ]
        ]);
    }

    /**
     * @see parent
     */
    public function advancedAdditional($observer)
    {
        $registration = $observer->getRegistration();
        if ($this->_connectorSettings->isToggled($this->getEndpointId(), $registration->getScope(), $registration->getScopeId())) {
            $this->endpointInfo($observer);
        }
    }

    /**
     * Creates and updates or deliveries associated with any alert
     *
     * @param mixed $observer
     */
    public function createNotifications($observer)
    {
        $data = $observer->getScript()->getObject();
        $results = [];
        if (isset($data['data']['items'])) {
            $items = $data['data']['items'];
            $results = $this->_manager->createAnnouncements($items);
        }
        $observer->getScript()->setResults($results);
    }
}

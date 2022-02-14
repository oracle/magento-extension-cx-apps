<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Browse\Event;

class Source implements \Oracle\M2\Connector\Event\SourceInterface, \Oracle\M2\Connector\Event\ContextProviderInterface
{
    protected $_settings;
    protected $_connector;

    /**
     * @param \Oracle\M2\Browse\SettingsInterface $settings
     * @param \Oracle\M2\Connector\SettingsInterface $connector
     */
    public function __construct(
        \Oracle\M2\Browse\SettingsInterface $settings,
        \Oracle\M2\Connector\SettingsInterface $connector
    ) {
        $this->_settings = $settings;
        $this->_connector = $connector;
    }

    /**
     * @see parent
     */
    public function create($browse)
    {
        return $this->_settings->createContext($browse);
    }

    /**
     * @see parent
     */
    public function getEventType()
    {
        return 'browse';
    }

    /**
     * @see parent
     */
    public function action($browse)
    {
        return 'add';
    }

    /**
     * @see parent
     */
    public function transform($browse)
    {
        if (!$browse->hasContext()) {
            $context = $this->_settings->createContext($browse);
        } else {
            $context = $browse->getContext();
        }
        $events = [];
        $eventDate = date('c', $this->_default($context, 'timestamp', time()));
        $siteId = $this->_settings->getSiteId('store', $context['store_id']);
        $siteHash = $this->_connector->getSiteId('store', $context['store_id']);
        if (array_key_exists('customer_email', $context)) {
            $events[] = [
                'siteId' => $siteId,
                'siteHash' => $siteHash,
                'customerId' => $context['customer_id'],
                'eventType' => 'EMAIL',
                'eventDate' => $eventDate,
                'value' => $context['customer_email'],
                'url' => $this->_default($context, 'url', null)
            ];
        }
        $events[] = [
            'siteId' => $siteId,
            'siteHash' => $siteHash,
            'customerId' => $context['customer_id'],
            'eventType' => $this->_default($context, 'event_type', 'VIEW'),
            'eventTypeValue' => $this->_default($context, 'event_type_value', null),
            'eventDate' => $eventDate,
            'value' => $this->_default($context, 'value', null),
            'url' => $this->_default($context, 'url', null)
        ];
        return ['events' => $events];
    }

    /**
     * Gets the value out of the map or default to defaultValue
     *
     * @param array $context
     * @param string $keyName
     * @param string $defaultValue
     * @return string
     */
    protected function _default($context, $keyName, $defaultValue)
    {
        if (array_key_exists($keyName, $context)) {
            return $context[$keyName];
        }
        return $defaultValue;
    }
}

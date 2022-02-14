<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Contact;

abstract class AttributeExtensionAbstract implements AttributeExtensionInterface
{
    /** @var \Oracle\M2\Contact\AttributeSettingsInterface  */
    protected $_settings;

    /**
     * @param \Oracle\M2\Contact\AttributeSettingsInterface $settings
     */
    public function __construct(
        \Oracle\M2\Contact\AttributeSettingsInterface $settings
    ) {
        $this->_settings = $settings;
    }

    /**
     * @see parent
     */
    public function contactAdditional($observer)
    {
        foreach ($this->_settings->getFields() as $fieldId => $field) {
            $observer->getEndpoint()->addFieldToExtension($this->_defaultGroup(), [
                'id' => $fieldId,
                'name' => $field['name'],
                'type' => 'select',
                'typeProperties' => [
                    'oracle' => [
                        'type' => 'contactField',
                        'displayType' => $field['type']
                    ]
                ]
            ]);
        }
    }

    /**
     * @see parent
     */
    public function contactLoadFields($observer)
    {
        $contact = $observer->getContact();
        $storeId = $observer->getStoreId();
        $container = $observer->getContainer();
        if ($this->_canMap($container->getAttributes())) {
            $extraFields = $this->_settings->getExtra($contact, $storeId);
            $container->setExtra($container->getExtra() + $extraFields);
        }
    }

    /**
     * Add the fields provided to the settings
     *
     * @return string
     */
    protected function _defaultGroup()
    {
        return 'attributes';
    }

    /**
     * Determines if a module setting is set in the settings
     *
     * @param array $attributes
     * @return boolean
     */
    protected function _canMap($attributes)
    {
        foreach ($this->_settings->getFields() as $fieldId => $field) {
            if (array_key_exists($fieldId, $attributes)) {
                return true;
            }
        }
        return false;
    }
}

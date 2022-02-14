<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Product;

abstract class CatalogMapperAbstract implements CatalogMapperInterface
{
    protected $_manager;

    /**
     * @param CatalogMapperManagerInterface $manager
     */
    public function __construct(CatalogMapperManagerInterface $manager)
    {
        $this->_manager = $manager;
    }

    /**
     * Gets a hashmap of applicable codes
     *
     * @return array
     */
    abstract public function getExtraFields();

    /**
     * Gets a hashmap of mapped default Fields
     *
     * @return array
     */
    abstract public function getDefaultFields();

    /**
     * Gets a collection of attribute objects
     *
     * @return array
     */
    abstract public function getFieldAttributes();

    /**
     * Determines if the mappings contains applicable codes
     *
     * @param array $mappings
     * @return boolean
     */
    public function isApplicableCodes($mappings)
    {
        foreach ($this->getExtraFields() as $code => $fieldLabel) {
            if (array_key_exists($code, $mappings)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @see parent
     */
    public function setExtraFields($observer)
    {
        $product = $observer->getProduct();
        $mappings = $observer->getMappings();
        $container = $observer->getContainer();
        if ($this->isApplicableCodes($mappings)) {
            $extra = $container->getFields();
            $object = $this->_manager->getByProduct($product);
            if ($object) {
                foreach ($this->getExtraFields() as $code => $fieldLabel) {
                    $extra[$code] = $object->getData($code);
                }
            }
            $container->setFields($extra);
        }
    }

    /**
     * @see parent
     */
    public function setDefaultFields($observer)
    {
        $fields = $observer->getContainer()->getFields();
        $allCodes = $this->getExtraFields();
        foreach ($this->getDefaultFields() as $defaultId => $fieldId) {
            $fields[$defaultId] = [$fieldId => $allCodes[$fieldId]];
        }
        $observer->getContainer()->setFields($fields);
    }

    /**
     * @see parent
     */
    public function setCustomFields($observer)
    {
        $fields = $observer->getContainer()->getFields();
        foreach ($this->getExtraFields() as $id => $name) {
            $fields[] = ['id' => $id, 'name' => $name];
        }
        $observer->getContainer()->setFields($fields);
    }

    /**
     * @see parent
     */
    public function setFieldAttributes($observer)
    {
        $fields = $observer->getContainer()->getFields();
        $allCodes = $this->getExtraFields();
        foreach ($this->getFieldAttributes() as $code => $type) {
            if (array_key_exists($code, $allCodes)) {
                $fields[] = [
                    'attribute_code' => $code,
                    'frontend_label' => $allCodes[$code],
                    'frontend_type' => $type,
                    'is_global' => true
                ];
            }
        }
        $observer->getContainer()->setFields($fields);
    }
}

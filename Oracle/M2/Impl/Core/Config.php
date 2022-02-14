<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Impl\Core;

class Config implements \Oracle\M2\Core\Config\ManagerInterface, \Oracle\M2\Core\Config\FactoryInterface
{
    protected $_reinitConfig;
    protected $_writeConfig;
    protected $_deleteConfig;
    protected $_dataFactory;

    /**
     * @param \Magento\Framework\App\Config\ReinitableConfigInterface $reinitConfig
     * @param \Magento\Framework\App\Config\Storage\WriterInterface $writeConfig
     * @param \Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory $dataFactory
     * @param \Oracle\M2\Impl\Core\DeleteConfig $deleteConfig
     */
    public function __construct(
        \Magento\Framework\App\Config\ReinitableConfigInterface $reinitConfig,
        \Magento\Framework\App\Config\Storage\WriterInterface $writeConfig,
        \Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory $dataFactory,
        \Oracle\M2\Impl\Core\DeleteConfig $deleteConfig
    ) {
        $this->_reinitConfig = $reinitConfig;
        $this->_writeConfig = $writeConfig;
        $this->_deleteConfig = $deleteConfig;
        $this->_dataFactory = $dataFactory;
    }

    /**
     * @see parent
     */
    public function getCollection()
    {
        return $this->_dataFactory->create();
    }

    /**
     * @see parent
     */
    public function save($path, $value, $scopeName, $scopeId)
    {
        // This is the place where they are storing config to the db.
        $this->_writeConfig->save($path, $value, $this->_scopeName($scopeName), $scopeId);
    }

    /**
     * @see parent
     */
    public function reinit()
    {
        $this->_reinitConfig->reinit();
    }

    /**
     * @see parent
     */
    public function deleteAll($path, $scopeName, $scopeId)
    {
        $this->_deleteConfig->deleteAll($path, $this->_scopeName($scopeName), $scopeId);
    }

    /**
     * Gets the correct scope name
     *
     * @param string $scopeName
     */
    public function _scopeName($scopeName)
    {
        if ($scopeName == 'store' || $scopeName == 'website') {
            $scopeName .= 's';
        }
        return $scopeName;
    }
}

<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Impl\Core;

use Magento\Backend\Setup\ConfigOptionsList;

class Meta implements \Oracle\M2\Core\MetaInterface
{

    const EXTENSION_VERSION = '0.1.0';

    protected $_meta;
    protected $_config;

    /**
     * @param \Magento\Framework\App\ProductMetadataInterface $meta
     * @param \Magento\Framework\App\DeploymentConfig $config
     */
    public function __construct(
        \Magento\Framework\App\ProductMetadataInterface $meta,
        \Magento\Framework\App\DeploymentConfig $config
    ) {
        $this->_meta = $meta;
        $this->_config = $config;
    }

    /**
     * @see parent
     */
    public function getName()
    {
        return $this->_meta->getName();
    }

    /**
     * @see parent
     */
    public function getVersion()
    {
        return $this->_meta->getVersion();
    }

    /**
     * @see parent
     */
    public function getEdition()
    {
        return $this->_meta->getEdition();
    }

    /**
     * @see parent
     */
    public function getExtensionVersion()
    {
        return self::EXTENSION_VERSION;
    }

    /**
     * @see parent
     */
    public function getAdminFrontName()
    {
        return $this->_config->get(ConfigOptionsList::CONFIG_PATH_BACKEND_FRONTNAME);
    }
}

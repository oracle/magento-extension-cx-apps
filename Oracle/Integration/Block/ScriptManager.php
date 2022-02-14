<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Integration\Block;

use Oracle\M2\Integration\ScriptManagerSettingsInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Filesystem\DriverInterface;
use Oracle\M2\Connector\RegistrationManagerInterface;

class ScriptManager extends \Magento\Framework\View\Element\Template
{
    /** @var \Oracle\M2\Connector\SettingsInterface $connectorSettings */
    protected $connectorSettings;

    /** @var Oracle\M2\Integration\ScriptManagerSettingsInterface $scriptManagerSettings */
    protected $scriptManagerSettings;

    /** @var \Oracle\M2\Connector\RegistrationManagerInterface */
    protected $registrationManager;

    /** @var \Oracle\M2\Core\Log\LoggerInterface $logger */
    protected $logger;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Oracle\M2\Integration\PopupSettingsInterface $helper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Oracle\M2\Connector\SettingsInterface $connectorSettings,
        \Oracle\M2\Integration\ScriptManagerSettingsInterface $scriptManagerSettings,
        RegistrationManagerInterface $registrationManager,
        DriverInterface $fileSystemDriver,
        \Oracle\M2\Core\Log\LoggerInterface $logger,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->connectorSettings = $connectorSettings;
        $this->scriptManagerSettings = $scriptManagerSettings;
        $this->registrationManager = $registrationManager;
        $this->fileSystemDriver = $fileSystemDriver;
        $this->logger = $logger;
    }

    /**
     * Determines if Script Manager is enabled
     *
     * @return bool
     */
    public function isEnabled()
    {
        return $this->scriptManagerSettings->isEnabled('store', $this->_storeManager->getStore()->getId());
    }

    /**
     * Gets the snippet code for Script Manager
     *
     * @return string
     */
    public function getSnippet()
    {
        $snippet = '';
        $storeId = $this->_storeManager->getStore(true)->getId();
        $registration = $this->registrationManager->getByScope('store', $storeId, true);
        if ($this->scriptManagerSettings->isEnabled($registration->getScope(), $registration->getScopeId())) {
            $eik = $this->connectorSettings->getEik($registration->getScope(), $registration->getScopeId());
            $endpoint = ScriptManagerSettingsInterface::SNIPPET_ENDPOINT_PREFIX
                . $eik . '/snippet.js';
            try {
                $snippet = $this->fileSystemDriver->fileGetContents($endpoint);
            } catch (FileSystemException $fse) {
                $this->logger->critical(
                    "Could not retrieve the Script Manager snippet code from {$endpoint}: "
                    . $fse->getMessage()
                );
            }
        }

        return $snippet;
    }
}

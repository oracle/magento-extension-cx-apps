<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Integration;

use Magento\Framework\Filesystem\DriverInterface;
use Oracle\M2\Impl\Core\Logger;

abstract class ExtensionAbstract implements \Oracle\M2\Connector\Discovery\ExtensionInterface, \Oracle\M2\Connector\Discovery\GroupInterface
{
    const EXT_DESCRIPTION_SCRIPT_MANAGER =  "Includes the Script Manager on each page of your site by default. "
        . "Enable and configure Oracle features using Script Manager from the "
        . "<a href='https://app.oracle.com/mail/pref/script_manager/'>Script Manager settings page</a>.";

    const EXT_DESCRIPTION_CART_RECOVERY = 'Send cart data from your Magento site to Oracle.';

    const EXT_DESCRIPTION_LEGACY_INTEGRATIONS = 'Custom integrations for Oracle features. These integrations do '
        . 'not need Script Manager snippet.';

    /** @var DriverInterface  */
    protected $fileSystemDriver;

    /** @var  Logger */
    protected $logger;

    /**
     * @param Logger $logger
     */
    public function __construct(
        DriverInterface $fileSystemDriver,
        Logger $logger
    ) {
        $this->fileSystemDriver = $fileSystemDriver;
        $this->logger = $logger;
    }

    /**
     * @see parent
     */
    public function getSortOrder()
    {
        return 90;
    }

    /**
     * @see parent
     */
    public function getEndpointName()
    {
        return 'Integrations';
    }

    /**
     * @see parent
     */
    public function getEndpointId()
    {
        return 'integration';
    }

    /**
     * @see parent
     */
    public function getEndpointIcon()
    {
        return 'mage-icon-integrations';
    }

    /**
     * @see parent
     */
    public function gatherEndpoints($observer)
    {
        $observer->getDiscovery()->addGroupHelper($this);
    }

    /**
     * @see parent
     */
    public function endpointInfo($observer)
    {
        $observer->getEndpoint()->addExtension([
            'sort_order' => 1,
            'definition' => [
                'id' => 'script_manager',
                'name' => 'Script Manager',
                'description' => self::EXT_DESCRIPTION_SCRIPT_MANAGER,
                'fields' => [
                    [
                        'id' => 'enabled',
                        'name' => 'Enabled',
                        'required' => true,
                        'type' => 'boolean',
                        'typeProperties' => [
                            'default' => false,
                            'oracle' => [ 'type' => 'script_manager' ]
                        ],
                        'position' => 1
                    ]
                ]
            ]
        ]);

        $observer->getEndpoint()->addExtension([
            'sort_order' => 2,
            'definition' => [
                'id' => 'cart_recovery',
                'name' => 'Cart Recovery',
                'description' => self::EXT_DESCRIPTION_CART_RECOVERY,
                'fields' => [
                    [
                        'id' => 'enabled',
                        'name' => 'Enabled',
                        'type' => 'boolean',
                        'required' => true,
                        'typeProperties' => [
                            'default' => false,
                            'oracle' => [ 'type' => 'cartRecovery' ]
                        ],
                        'position' => 1
                    ]
                ]
            ]
        ]);

        $observer->getEndpoint()->addExtension([
            'sort_order' => 3,
            'definition' => [
                'id' => 'legacyIntegrations',
                'name' => 'Legacy Integrations',
                'description' => self::EXT_DESCRIPTION_LEGACY_INTEGRATIONS,
                'category' => 'ui_context_element'
            ]
        ]);

        $observer->getEndpoint()->addExtension([
            'sort_order' => 5,
            'definition' => [
                'id' => 'coupon_manager',
                'name' => 'Coupon Manager',
                'fields' => [
                    [
                        'id' => 'enabled',
                        'name' => 'Enabled',
                        'type' => 'boolean',
                        'required' => true,
                        'typeProperties' => [ 'default' => false ]
                    ],
                ]
            ]
        ]);

        $observer->getEndpoint()->addExtension([
            'sort_order' => 6,
            'definition' => [
                'id' => 'popup_manager',
                'name' => 'Pop-Up Manager',
                'fields' => [
                    [
                        'id' => 'enabled',
                        'name' => 'Enabled',
                        'type' => 'boolean',
                        'required' => true,
                        'typeProperties' => [ 'default' => false ]
                    ],
                    [
                        'id' => 'popups',
                        'name' => 'Domain',
                        'type' => 'select',
                        'depends' => [
                            [
                                'id' => 'enabled',
                                'values' => [true]
                            ]
                        ],
                        'typeProperties' => [
                            'oracle' => [ 'type' => 'popupManager' ],
                            'multiple' => true
                        ]
                    ],
                    [
                        'id' => 'createSubscribers',
                        'name' => 'Subscribe to Newsletter',
                        'type' => 'boolean',
                        'required' => true,
                        'typeProperties' => [ 'default' => false ],
                        'depends' => [
                            [ 'id' => 'enabled', 'values' => [true] ]
                        ]
                    ]
                ]
            ]
        ]);

        $observer->getEndpoint()->addAutoConfigData(
            $this->getEndpointId(),
            $observer->getRegistration()->getScopeHash(),
            $this->getAutoConfigData('integration')
        );
    }

    /**
     * @param string $extensionName Otherwise known as the module name
     * @return []
     */
    protected function getAutoConfigData($extensionName)
    {
        $autoConfigDir = __DIR__ . '/../Connector/Discovery/AutoConfig';
        $autoConfigFile = $this->fileSystemDriver->getRealPath("{$autoConfigDir}/{$extensionName}.json");
        if (!$this->fileSystemDriver->isReadable($autoConfigFile)) {
            $this->logger->debug(
                "AutoConfig filepath {$autoConfigFile} does not exist or cannot be read for the "
                . "{$extensionName} extension."
            );
            return [];
        }

        return json_decode($this->fileSystemDriver->fileGetContents($autoConfigFile));
    }

    /**
     * @see parent
     */
    public function advancedAdditional($observer)
    {
        $observer->getEndpoint()->addOptionToScript('event', 'moduleSettings', [
            'id' => $this->getEndpointId(),
            'name' => $this->getEndpointName()
        ]);
    }
}

<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Coupon\Model;

class Observer extends \Oracle\M2\Coupon\ExtensionAbstract
{
    protected $_formatHelper;

    /**
     * @param \Magento\SalesRule\Helper\Coupon $formatHelper
     * @param \Oracle\M2\Core\Sales\RuleManagerInterface $rules
     * @param \Oracle\M2\Coupon\ManagerInterface $manager
     * @param \Oracle\M2\Connector\MiddlewareInterface $middleware
     * @param \Oracle\M2\Core\App\EmulationInterface $appEmulation
     * @param \Oracle\M2\Core\Store\ManagerInterface $storeManager
     * @param \Oracle\M2\Connector\QueueManagerInterface $queueManager
     * @param \Oracle\M2\Connector\SettingsInterface $connectorSettings
     * @param \Oracle\M2\Coupon\SettingsInterface $helper
     * @param \Oracle\M2\Connector\Event\PlatformInterface $platform
     * @param \Oracle\M2\Connector\Event\SourceInterface $source
     * @param \Magento\Framework\Filesystem\DriverInterface $fileSystemDriver
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\SalesRule\Helper\Coupon $formatHelper,
        \Oracle\M2\Core\Sales\RuleManagerInterface $rules,
        \Oracle\M2\Coupon\ManagerInterface $manager,
        \Oracle\M2\Connector\MiddlewareInterface $middleware,
        \Oracle\M2\Core\App\EmulationInterface $appEmulation,
        \Oracle\M2\Core\Store\ManagerInterface $storeManager,
        \Oracle\M2\Connector\QueueManagerInterface $queueManager,
        \Oracle\M2\Connector\SettingsInterface $connectorSettings,
        \Oracle\M2\Coupon\SettingsInterface $helper,
        \Oracle\M2\Connector\Event\PlatformInterface $platform,
        \Oracle\M2\Coupon\Event\Source $source,
        \Magento\Framework\Filesystem\DriverInterface $fileSystemDriver,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::__construct(
            $rules,
            $manager,
            $middleware,
            $appEmulation,
            $storeManager,
            $queueManager,
            $connectorSettings,
            $helper,
            $platform,
            $source,
            $fileSystemDriver,
            $logger
        );
        $this->_formatHelper = $formatHelper;
    }

    /**
     * @see parent
     */
    public function translate($message)
    {
        return __($message);
    }

    /**
     * @see parent
     */
    protected function _couponFormats()
    {
        $formats = [];
        foreach ($this->_formatHelper->getFormatsList() as $id => $name) {
            $formats[] = [ 'id' => $id, 'name' => $name ];
        }
        return $formats;
    }
}

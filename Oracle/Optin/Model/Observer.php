<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Optin\Model;

class Observer extends \Oracle\M2\Optin\ExtensionAbstract
{
    protected $_checkout;

    /**
     * @param \Magento\Checkout\Model\Session $checkout
     * @param \Oracle\M2\Core\Subscriber\ManagerInterface $subscribers
     * @param \Oracle\M2\Core\App\EmulationInterface $appEmulation
     * @param \Oracle\M2\Core\Store\ManagerInterface $storeManager
     * @param \Oracle\M2\Connector\QueueManagerInterface $queueManager
     * @param \Oracle\M2\Connector\SettingsInterface $connectorSettings
     * @param \Oracle\M2\Connector\Event\PlatformInterface $platform
     * @param \Oracle\M2\Optin\Event\Source $source
     * @param \Oracle\M2\Optin\SettingsInterface $helper
     * @param \Magento\Framework\Filesystem\DriverInterface $fileSystemDriver
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkout,
        \Oracle\M2\Core\Subscriber\ManagerInterface $subscribers,
        \Oracle\M2\Core\App\EmulationInterface $appEmulation,
        \Oracle\M2\Core\Store\ManagerInterface $storeManager,
        \Oracle\M2\Connector\QueueManagerInterface $queueManager,
        \Oracle\M2\Connector\SettingsInterface $connectorSettings,
        \Oracle\M2\Connector\Event\PlatformInterface $platform,
        \Oracle\M2\Optin\Event\Source $source,
        \Oracle\M2\Optin\SettingsInterface $helper,
        \Magento\Framework\Filesystem\DriverInterface $fileSystemDriver,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::__construct(
            $subscribers,
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
        $this->_checkout = $checkout;
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
    public function subscribeAfterCheckout($observer)
    {
        $quote = $observer->getQuote();
        $optin = (bool) $this->_checkout->getSubscribeToNewsletter();
        $this->_subscribeAfterCheckout(
            $quote->getStoreId(),
            $quote->getCustomerEmail(),
            $optin
        );
    }

    /**
     * @see parent
     */
    protected function _checkoutLayouts()
    {
        $layouts = parent::_checkoutLayouts();
        unset($layouts[1]);
        unset($layouts[3]);
        return $layouts;
    }
}

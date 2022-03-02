<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Connector\Discovery;

abstract class ExtensionPushEventAbstract implements \Oracle\M2\Connector\Discovery\PushChangesInterface, \Oracle\M2\Connector\Discovery\TranslationInterface
{
    /** @var \Oracle\M2\Connector\Event\HelperInterface */
    protected $_helper;

    /** @var \Oracle\M2\Connector\Event\PlatformInterface */
    protected $_platform;

    /** @var \Oracle\M2\Connector\Event\SourceInterface */
    protected $_source;

    /** @var \Oracle\M2\Connector\Event\PushLogic */
    protected $_pushLogic;

    /** @var \Oracle\M2\Core\Store\ManagerInterface */
    protected $_storeManager;

    /** @var \Oracle\M2\Connector\SettingsInterface */
    protected $_connectorSettings;

    /**
     * @param \Oracle\M2\Core\Store\ManagerInterface $storeManager
     * @param \Oracle\M2\Connector\QueueManagerInterface $queueManager
     * @param \Oracle\M2\Connector\SettingsInterface $connectorSettings
     * @param \Oracle\M2\Connector\Event\HelperInterface $helper
     * @param \Oracle\M2\Connector\Event\PlatformInterface $platform
     * @param \Oracle\M2\Connector\Event\SourceInterface $source
     */
    public function __construct(
        \Oracle\M2\Core\Store\ManagerInterface $storeManager,
        \Oracle\M2\Connector\QueueManagerInterface $queueManager,
        \Oracle\M2\Connector\SettingsInterface $connectorSettings,
        \Oracle\M2\Connector\Event\HelperInterface $helper,
        \Oracle\M2\Connector\Event\PlatformInterface $platform,
        \Oracle\M2\Connector\Event\SourceInterface $source
    ) {
        $this->_helper = $helper;
        $this->_platform = $platform;
        $this->_source = $source;
        $this->_storeManager = $storeManager;
        $this->_connectorSettings = $connectorSettings;
        $this->_pushLogic = new \Oracle\M2\Connector\Event\PushLogic(
            $queueManager,
            $connectorSettings,
            $helper,
            $platform,
            $source,
            $this->_contextProvider()
        );
    }

    /**
     * @see \Oracle\M2\Connector\Discovery\PushChangesInterface::pushChanges
     * @param \Magento\Framework\Event\Observer|\Oracle\M2\Core\DataObject $observer
     */
    public function pushChanges($observer)
    {
        $object = $this->_getObject($observer);
        $storeId = $object->getStoreId();
        if (is_null($storeId) || $storeId === false) {
            $storeId = true;
        }
        if($observer->hasData("resetPassword")) {
            $object->setData("resetPassword", $observer->getData("resetPassword"));
        }
        if($observer->hasData("forgotPassword")) {
            $object->setData("forgotPassword", $observer->getData("forgotPassword"));
        }
        $this->_pushLogic->pushEvent($object, $storeId);
    }

    /**
     * Yanks the object out of this observed change
     *
     * @param mixed $observer
     * @return mixed
     */
    protected function _getObject($observer)
    {
        return $observer->getData($this->_source->getEventType());
    }

    /**
     * Gets a context provider transformer
     *
     * @return \Oracle\M2\Connector\Event\ContextProviderInterface|null
     */
    protected function _contextProvider()
    {
        return null;
    }

    /**
     * @param \Oracle\M2\Connector\Event\HelperInterface $helper
     * @return self
     */
    protected function setHelper(\Oracle\M2\Connector\Event\HelperInterface $helper)
    {
        $this->_helper = $helper;
        return $this;
    }

    /**
     * @return \Oracle\M2\Connector\Event\HelperInterface
     */
    protected function getHelper()
    {
        return $this->_helper;
    }
}

<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Connector\Event;

class PushLogic
{
    protected $_queueManager = null;
    protected $_connector = null;
    protected $_helper = null;
    protected $_platform = null;
    protected $_source = null;
    protected $_context = null;

    /**
     * @param \Oracle\M2\Connector\QueueManagerInterface $queueManager
     * @param \Oracle\M2\Connector\SettingsInterface $connector
     * @param \Oracle\M2\Connector\Event\HelperInterface $helper
     * @param \Oracle\M2\Connector\Event\PlatformInterface $platform
     * @param \Oracle\M2\Connector\Event\SourceInterface $source
     * @param \Oracle\M2\Connector\Event\ContextProviderInterface $context
     */
    public function __construct(
        \Oracle\M2\Connector\QueueManagerInterface $queueManager,
        \Oracle\M2\Connector\SettingsInterface $connector,
        HelperInterface $helper,
        PlatformInterface $platform,
        SourceInterface $source,
        ContextProviderInterface $context = null
    ) {
        $this->_queueManager = $queueManager;
        $this->_connector = $connector;
        $this->_helper = $helper;
        $this->_platform = $platform;
        $this->_source = $source;
        // This isn't great, but works for now
        if (is_null($context) && $source instanceof ContextProviderInterface) {
            $context = $source;
        }
        $this->_context = $context;
    }

    /**
     * Does the appropriate push on the object
     *
     * @param mixed $object
     * @param mixed $storeId
     * @param boolean $foreground
     * @param boolean $fallbackPersist
     * @return boolean
     */
    public function pushEvent($object, $storeId = null, $foreground = true, $fallbackPersist = true)
    {
        if (!$this->_connector->isTestMode('store', $storeId) && $this->_helper->isEnabled('store', $storeId)) {
            $action = $this->_source->action($object);
            if (!empty($action)) {
                if ($foreground && $this->_connector->isEventQueued('store', $storeId)) {
                    $event = $this->_platform->annotate(new QueuableSource($this->_source, $this->_context), $object, $action, $storeId);
                    return $this->_queueManager->save($event);
                } else {
                    $event = $this->_platform->annotate($this->_source, $object, $action, $storeId);
                    return $this->_platform->dispatchToResponsys($event) || (
                        $fallbackPersist &&
                        $this->_queueManager->save($event)
                    );
                }
            }
        }
        return false;
    }
}

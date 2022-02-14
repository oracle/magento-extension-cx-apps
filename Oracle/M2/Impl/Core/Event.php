<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Impl\Core;

class Event implements \Oracle\M2\Core\Event\ManagerInterface
{
    private $_events;

    public function __construct(
        \Magento\Framework\Event\ManagerInterface $events
    ) {
        $this->_events = $events;
    }

    /**
     * @see parent
     */
    public function dispatch($eventName, array $data = [])
    {
        $this->_events->dispatch($eventName, $data);
    }
}

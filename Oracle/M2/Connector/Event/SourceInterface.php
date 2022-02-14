<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Connector\Event;

interface SourceInterface
{
    const ADD_ACTION = 'add';
    const UPDATE_ACTION = 'update';
    const DELETE_ACTION = 'delete';
    const REPLACE_ACTION = 'replace';

    /**
     * Transform arbitrary Magento data into a consumable event
     *
     * @param mixed $object
     * @return array
     */
    public function transform($object);

    /**
     * Determines which action the object should be
     *
     * @param mixed $object
     * @return string
     */
    public function action($object);

    /**
     * Gets the handled event type
     *
     * @return string
     */
    public function getEventType();
}

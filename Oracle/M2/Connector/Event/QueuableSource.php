<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Connector\Event;

class QueuableSource implements SourceInterface
{
    protected $_source;
    protected $_context;

    /**
     * @param SourceInterface $source
     * @param ContextProviderInterface $context
     */
    public function __construct(
        SourceInterface $source,
        ContextProviderInterface $context = null
    ) {
    
        $this->_source = $source;
        $this->_context = $context;
    }

    /**
     * @see parent
     */
    public function action($object)
    {
        return $this->_source->action($object);
    }

    /**
     * @see parent
     */
    public function getEventType()
    {
        return $this->_source->getEventType();
    }

    /**
     * @see parent
     */
    public function transform($object)
    {
        if (!is_null($this->_context)) {
            $queue = [
                'id' => $object->getId(),
                'storeId' => $object->getStoreId(),
                'area' => 'frontend'
            ];
            $context = $this->_context->create($object);
            return [
                'context' => [
                    'event' => [
                        $this->getEventType() => $queue + $context
                    ]
                ]
            ];
        } else {
            return $this->_source->transform($object);
        }
    }
}

<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Email\Event;

class Source implements \Oracle\M2\Connector\Event\SourceInterface
{
    /**
     * @see parent
     */
    public function getEventType()
    {
        return 'message';
    }

    /**
     * @see parent
     */
    public function action($object)
    {
        return 'update';
    }

    /**
     * @see parent
     */
    public function transform($object)
    {
        $template = $object->getTemplate();
        $message = $object->getMessage();
        return [
            'id' => $template['messageId'],
            'context' => [
                'fields' => $message['fields']
            ],
            'content' => [
                [
                    'type' => 'text',
                    'subject' => strip_tags($message['subject']),
                    'content' => strip_tags($message['content'])
                ],
                [
                    'type' => 'html',
                    'subject' => $message['subject'],
                    'content' => $message['content']
                ]
            ]
        ];
    }
}

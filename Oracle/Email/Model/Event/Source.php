<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Email\Model\Event;

class Source implements \Oracle\M2\Connector\Event\SourceInterface
{
    /**
     * @see parent
     */
    public function getEventType()
    {
        return 'delivery';
    }

    /**
     * @see parent
     */
    public function action($message)
    {
        return 'add';
    }

    /**
     * @see parent
     * @param \Magento\Framework\Mail\MailMessageInterface | \Magento\Framework\Mail\EmailMessageInterface $message
     */
    public function transform($message)
    {
        $headers = $message->getHeaders();
        if ($headers instanceof \Zend\Mail\Headers) {
            $fromHeader = $headers->get('From') ? $headers->get('From')->toString() : '';
        } else {
            $fromHeader = is_array($headers['From']) ? implode(' ', $headers['From']) : $headers['From'];
        }
        
        $messageFrom = $message->getFrom();
        $fromEmail = $messageFrom;
        $fromName = $messageFrom;
        
        if (is_array($messageFrom) && isset($messageFrom['email'])) {
                $fromEmail = $messageFrom['email'];
                $fromName = isset($messageFrom['name']) ? $messageFrom['name'] : $fromEmail;
        }

        // If $fromHeader came from a Zend\Mail\Header\From instance it will have 'From: ' prepended. We want to strip
        // that out of the fromName if it's there, along with any <emailaddress> part.
        if (preg_match('/(?:^From:)?([^<]+)/s', $fromHeader, $matches)) {
            $fromName = trim($matches[1]);
        }
        $delivery = $message->getDelivery();
        $delivery['fromEmail'] = $fromEmail;
        $delivery['fromName'] = $fromName;
        return $delivery;
    }
}

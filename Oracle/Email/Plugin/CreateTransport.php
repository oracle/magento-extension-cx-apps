<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Email\Plugin;

class CreateTransport
{
    protected $_transportFactory;
    protected $_helper;

    /**
     * @param \Oracle\M2\Email\SettingsInterface $helper
     * @param \Oracle\Email\Model\TransportFactory $transportFactory
     */
    public function __construct(
        \Oracle\M2\Email\SettingsInterface $helper,
        \Oracle\Email\Model\TransportFactory $transportFactory
    ) {
        $this->_helper = $helper;
        $this->_transportFactory = $transportFactory;
    }

    /**
     * Intercept the creation of internal mail transports
     *
     * @param mixed $subject
     * @param callable $create
     * @param array $data
     * @return \Magento\Framework\Mail\TransportInterface
     */
    public function aroundCreate($subject, $create, $data)
    {
        // This file will be useful for creating transport class for oracle rather than using the default one.

        if (array_key_exists('message', $data)) {
            if ($this->_helper->isOracleMessage($data['message'])) {
                return $this->_transportFactory->create($data);
            }
        }
        return $create($data);
    }
}

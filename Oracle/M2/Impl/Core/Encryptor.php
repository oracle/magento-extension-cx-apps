<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Impl\Core;

class Encryptor implements \Oracle\M2\Core\EncryptorInterface
{
    private $_encryptor;

    /**
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     */
    public function __construct(
        \Magento\Framework\Encryption\EncryptorInterface $encryptor
    ) {
        $this->_encryptor = $encryptor;
    }

    /**
     * @see parent
     */
    public function encrypt($message)
    {
        return $this->_encryptor->encrypt($message);
    }

    /**
     * @see parent
     */
    public function decrypt($message)
    {
        return $this->_encryptor->decrypt($message);
    }
}

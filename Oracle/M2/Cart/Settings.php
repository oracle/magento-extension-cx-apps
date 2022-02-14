<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Cart;

class Settings extends \Oracle\M2\Integration\CartSettings implements \Oracle\M2\Cart\SettingsInterface
{
    const ORACLE_CART_EMAIL = '__bmec_em';

    protected $_writer;

    /**
     * @param \Oracle\M2\Core\Cookie\WriterInterface $writer
     * @param \Oracle\M2\Core\EncryptorInterface $encrypt
     * @param \Oracle\M2\Core\Cookie\ReaderInterface $cookies
     * @param \Oracle\M2\Core\Config\ScopedInterface $config
     * @param \Oracle\M2\Core\Store\UrlManagerInterface $urls
     */
    public function __construct(
        \Oracle\M2\Core\Cookie\WriterInterface $writer,
        \Oracle\M2\Core\EncryptorInterface $encrypt,
        \Oracle\M2\Core\Cookie\ReaderInterface $cookies,
        \Oracle\M2\Core\Config\ScopedInterface $config,
        \Oracle\M2\Core\Store\UrlManagerInterface $urls
    ) {
        parent::__construct($encrypt, $cookies, $config, $urls);
        $this->_writer = $writer;
    }

    /**
     * @see parent
     */
    public function isEnabled($scope = 'default', $scopeId = null)
    {
          return (
              $this->isCartRecoveryEnabled($scope, $scopeId)
          );
    }

    /**
     * @see parent
     */
    public function isToggled($scope = 'default', $scopeId = null)
    {
        return $this->_config->getValue(self::XML_PATH_API_TOGGLE, $scope, $scopeId) == 'api';
    }

    /**
     * @see parent
     */
    public function isShadowDom($scopeType = 'default', $scopeId = null)
    {
        return !$this->isToggled($scopeType, $scopeId) && parent::isShadowDom($scopeType, $scopeId);
    }

    /**
     * @see parent
     */
    public function getCartRecoveryEmail($quote)
    {
        $email = parent::getCartRecoveryEmail($quote);
        if (empty($email)) {
            $encoded = $this->_cookies->getCookie(self::ORACLE_CART_EMAIL, '');
            if (empty($encoded)) {
                return $email;
            }
            $encypted = rawurldecode($encoded);
            $email = $this->_encrypt->decrypt($encypted);
        }
        return $email;
    }

    /**
     * @see parent
     */
    public function getCartRecoverySelectors($scope = 'default', $scopeId = null)
    {
        return $this->_config->getValue(self::XML_PATH_RECOVERY_EMAIL, $scope, $scopeId);
    }

    /**
     * @see parent
     */
    public function setCartRecoveryCookie($email)
    {
        $encrypted = $this->_encryptEmail($email);
        if ($encrypted) {
            $this->_writer->setServerCookie(self::ORACLE_CART_EMAIL, $encrypted);
        }
    }

    /**
     * @see parent
     */
    protected function _encryptEmail($email)
    {
        return rawurlencode($this->_encrypt->encrypt($email));
    }
}

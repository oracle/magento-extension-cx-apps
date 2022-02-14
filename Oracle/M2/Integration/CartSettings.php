<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Integration;

class CartSettings extends \Oracle\M2\Core\Config\ContainerAbstract implements CartSettingsInterface
{
    const ORACLE_CR_EMAIL = '__btr_em';
    const REDIRECT_PATH = 'oracle/redirect/index';

    protected $_cookies;
    protected $_encrypt;
    protected $_urls;

    /**
     * @param \Oracle\M2\Core\EncryptorInterface $encrypt
     * @param \Oracle\M2\Core\Cookie\ReaderInterface $cookies
     * @param \Oracle\M2\Core\Config\ScopedInterface $config
     * @param \Oracle\M2\Core\Store\UrlManagerInterface $urls
     */
    public function __construct(
        \Oracle\M2\Core\EncryptorInterface $encrypt,
        \Oracle\M2\Core\Cookie\ReaderInterface $cookies,
        \Oracle\M2\Core\Config\ScopedInterface $config,
        \Oracle\M2\Core\Store\UrlManagerInterface $urls
    ) {
        parent::__construct($config);
        $this->_cookies = $cookies;
        $this->_encrypt = $encrypt;
        $this->_urls = $urls;
    }

    /**
     * @see parent
     */
    public function isCartRecoveryEnabled($scopeType = 'default', $scopeId = null)
    {
        return $this->_config->isSetFlag(self::XML_PATH_RECOVERY_ENABLED, $scopeType, $scopeId);
    }

    /**
     * @see parent
     */
    public function getCartRecoveryEmbedCode($scopeType = 'default', $scopeId = null)
    {
        return $this->_config->getValue(self::XML_PATH_RECOVERY_EMBED, $scopeType, $scopeId);
    }

    /**
     * @see parent
     */
    public function getCartRecoveryEmail($quote)
    {
        if ($quote->getCustomerEmail()) {
            return $quote->getCustomerEmail();
        } else {
            // Attempt to read the oracle email capture cookie
            $encoded = $this->_cookies->getCookie(self::ORACLE_CR_EMAIL, '');
            if (empty($encoded)) {
                return null;
            }
            return base64_decode(str_pad(strtr($encoded, '-_', '+/'), strlen($encoded) % 4, '='));
        }
    }

    /**
     * @see parent
     */
    public function getRedirectUrl($modelId, $store, $modelType = 'cart')
    {
        return $this->_urls->getFrontendUrl($store, self::REDIRECT_PATH, [
            '_nosid' => true,
            'service' => 'email',
            'type' => $modelType,
            'id' => urlencode(base64_encode($this->_encrypt->encrypt($modelId)))
        ]);
    }

    /**
     * @see parent
     */
    public function isShadowDom($scopeType = 'default', $scopeId = null)
    {
        return (
            $this->isCartRecoveryEnabled($scopeType, $scopeId) &&
            $this->getCartRecoveryEmbedCode($scopeType, $scopeId)
        );
    }

    /**
     * @see \Oracle\M2\Integration\CartSettingsInterface::isTaxIncluded
     * @param string $scopeType
     * @param int $scopeId [null]
     * @return boolean
     */
    public function isTaxIncluded($scopeType = 'default', $scopeId = null)
    {
        return $this->_config->getValue(self::XML_PATH_RECOVERY_TAX_INCLUDED, $scopeType, $scopeId);
    }
}

<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Connector\Model;

class Registration extends \Magento\Framework\Model\AbstractModel implements \Oracle\M2\Connector\RegistrationInterface
{
    const PLATFORM_SUFFIX = 'oracle';

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Oracle\Connector\Model\ResourceModel\Registration');
    }

    /**
     * Attempts to load a registration by scope
     *
     * @param string $scopeType
     * @param string $scopeId
     * @return $this
     */
    public function loadByScope($scopeType, $scopeId)
    {
        $this->_getResource()->loadByScope($this, $scopeType, $scopeId);
        return $this;
    }

    /**
     * @see parent
     */
    public function setScopeHash($hash)
    {
        list($scopeName, $scopeId, $scopeCode) = explode('.', $hash);
        return $this
            ->setScope($scopeName)
            ->setScopeId($scopeId)
            ->setScopeCode($scopeCode);
    }

    /**
     * @see parent
     */
    public function getScopeHash($includeCode = false)
    {
        $things = [$this->getScope(), $this->getScopeId()];
        if ($includeCode) {
            $things[] = $this->getScopeCode();
        }
        return implode('.', $things);
    }

    /**
     * @see parent
     */
    public function getName()
    {
        return $this->getData(self::NAME);
    }

    /**
     * @see parent
     */
    public function getEnvironment()
    {
        return $this->getData(self::ENVIRONMENT);
    }

    /**
     * @see parent
     */
    public function getConnectorKey()
    {
        return $this->getData(self::CONNECTOR_KEY);
    }

    /**
     * @see parent
     */
    public function getScope()
    {
        return $this->getData(self::SCOPE_NAME);
    }

    /**
     * @see parent
     */
    public function getScopeId()
    {
        return $this->getData(self::SCOPE_ID);
    }

    /**
     * @see parent
     */
    public function getScopeCode()
    {
        return $this->getData(self::SCOPE_CODE);
    }

    /**
     * @see parent
     */
    public function getIsActive()
    {
        return $this->getData(self::IS_ACTIVE);
    }

    /**
     * @see parent
     */
    public function getIsProtected()
    {
        return $this->getData(self::IS_PROTECTED);
    }

    /**
     * @see parent
     */
    public function getUsername()
    {
        return $this->getData(self::USERNAME);
    }

    /**
     * @see parent
     */
    public function getPassword()
    {
        return $this->getData(self::PASSWORD);
    }

    /**
     * @see parent
     */
    public function getPlatformSuffix()
    {
        return self::PLATFORM_SUFFIX;
    }
}

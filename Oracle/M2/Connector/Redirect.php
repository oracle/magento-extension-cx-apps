<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Connector;

class Redirect
{
    private $_params = [];
    private $_path;
    private $_isReferer = false;

    /**
     * Gets the referer flag
     *
     * @return boolean
     */
    public function getIsReferer()
    {
        return $this->_isReferer;
    }

    /**
     * Sets a referer
     *
     * @param boolean $referer
     * @return $this
     */
    public function setIsReferer($referer)
    {
        $this->_isReferer = $referer;
        return $this;
    }

    /**
     * Has the path ever been set?
     *
     * @return boolean
     */
    public function isPathEmpty()
    {
        return is_null($this->_path);
    }

    /**
     * Sets the redirect path
     *
     * @param string $param
     * @return $this
     */
    public function setPath($path)
    {
        $this->_path = $path;
        return $this;
    }

    /**
     * Sets any redirect arguments on the path
     *
     * @param string $key
     * @param string $value
     * @return $this
     */
    public function setParam($key, $value)
    {
        $this->_params[$key] = $value;
        return $this;
    }

    /**
     * Unsets any params
     *
     * @param string $key
     * @return $this
     */
    public function unsetParam($key)
    {
        unset($this->_params[$key]);
        return $this;
    }

    /**
     * Unsets a list of params
     *
     * @param array $keys
     * @return $this
     */
    public function unsetParams(array $keys = [])
    {
        foreach ($keys as $key) {
            $this->unsetParam($key);
        }
        return $this;
    }

    /**
     * Sets a collection of params on the redirect
     *
     * @param array $params
     * @return $this
     */
    public function setParams($params)
    {
        $this->_params = $params;
        return $this;
    }

    /**
     * Gets any redirect path
     *
     * @return string
     */
    public function getPath()
    {
        return $this->_path;
    }

    /**
     * Gets any redirect param
     *
     * @return array
     */
    public function getParams()
    {
        return $this->_params;
    }
}

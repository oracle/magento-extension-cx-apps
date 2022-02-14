<?php

namespace Oracle\M2\Common\StandardResource;

use Oracle\M2\Common\Utils;

/**
 * A resource proxy is to "classify" certain PHP functions
 * to make them both Object Oriented and testable.
 *
 * @author Philip Cali <philip.cali@oracle.com>
 */
class Proxy
{
    protected $_resource;
    protected $_prefix;
    protected $_excluded;

    /**
     * Construct the resource proxy with a prefix
     *
     * @param string $prefix
     * @param resource $resource
     */
    public function __construct($prefix, $excluded = array(), $resource = null)
    {
        $this->_prefix = $prefix;
        $this->_excluded = $excluded;
        $this->_resource = $resource;
    }

    /**
     * Gets the underlying PHP resource
     *
     * @return resource
     */
    public function getResource()
    {
        return $this->_resource;
    }

    /**
     * Sets the exclusion name
     *
     * @param string $name
     * @return self
     */
    public function addExcluded($name)
    {
        $this->_excluded[$name] = true;
        return $this;
    }

    /**
     * Get the underlying PHP function prefix
     *
     * @return string
     */
    public function getPrefix()
    {
        return $this->_prefix;
    }

    public function byRef($name, &$value)
    {
        $function = $this->_prefix . Utils::underscore($name);
        $return = call_user_func_array($function, array($this->_resource, &$value));
        return $return;
    }

    /**
     * Proxy PHP functions
     *
     * @param string $name
     * @param array $args
     * @return mixed
     */
    public function __call($name, $args)
    {
        $function = $this->_prefix . Utils::underscore($name);
        if (!function_exists($function)) {
            throw new \BadMethodCallException("Resource function {$function} does not exist.");
        }
        if (!array_key_exists($name, $this->_excluded) && !is_null($this->_resource)) {
            array_unshift($args, $this->_resource);
        }
        $return = call_user_func_array($function, $args);
        if ($name == 'close') {
            unset($this->_resource);
            $this->_resource = null;
            return $this;
        }
        if (is_resource($return)) {
            $this->_resource = $return;
            return $this;
        }
        return $return;
    }

    /**
     * Set a cleanup to close the attached resource
     *
     * @see parent
     */
    public function __destruct()
    {
        if (!is_null($this->_resource)) {
            $this->close();
        }
    }
}

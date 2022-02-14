<?php

namespace Oracle\M2\Common;

use Oracle\M2\Common\Functional\Option;

/**
 * Upgraded data container with chainable setters menthods
 */
class DataObject
{
    protected $_data;
    protected $_underscore;

    /**
     * Wrap an array holding the concrete data
     *
     * @param array $data
     */
    public function __construct(array $data = array(), $underscore = false)
    {
        $this->_data = $data;
        $this->_underscore = $underscore;
    }

    /**
     * A host of magic methods for dealing with internal data:
     *
     * @param string $name
     * - getSomething(): $this->_data['something']
     * - safeSomething(): Option version of the previous
     * - unsetSomething(): unset($this->_data['something'])
     * - hasSomething(): isset($this->_data['something'])
     * - setSomething($value): $this->_data['something'] = $value
     * - withSomething($value): ''
     * - incrementSomething(): Will add 1 or more values to 'something'
     * - decrementSomething(): Will decrement 1 or more values to 'something'
     * @param array $args
     * @return mixed
     */
    public function __call($name, $args)
    {
        list($prefix, $camelized) = $this->_camelizedValue($name);
        switch ($prefix) {
        case 'get':
            $option = $this->_safe($camelized);
            if (!$option->isEmpty()) {
                return $option->get();
            }
            $this->_throwException("\\BadMethodCallException", "Could not find a value for $name in %s");
            break;
        case 'safe':
            return $this->_safe($camelized);
        case 'unset':
            unset($this->_data[$camelized]);
            break;
        case 'has':
            return array_key_exists($camelized, $this->_data);
        case 'set':
        case 'with':
            $this->_set($camelized, $args[0]);
            break;
        case 'increment':
        case 'decrement':
            $amount = isset($args[0]) ? $args[0] : 1;
            $amount *= ($prefix == 'decrement' ? -1 : 1);
            $original = $this->_safe($camelized)->getOrElse(0);
            $this->_set($camelized, $original + $amount);
            break;
        default:
            return $this->_defaultMethod($prefix, $camelized, $args);
        }
        return $this;
    }

    /**
     * Safely retrieves the data for the camelized key
     *
     * @param string $camelized
     * @return Option
     */
    protected function _safe($camelized)
    {
        if (array_key_exists($camelized, $this->_data)) {
            return new Functional\Some($this->_data[$camelized]);
        }
        return new Functional\None();
    }

    /**
     * Gets the value associated with the data array
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        if (!array_key_exists($name, $this->_data)) {
            return null;
        }
        return $this->_data[$name];
    }

    /**
     * Sets the value associated on the data array
     *
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        $this->_data[$name] = $value;
    }

    /**
     * Allows the complete replacement of underlying data
     *
     * @param array $data
     * @return Object
     */
    public function replace(array $data = array())
    {
        $this->_data = $data;
        return $this;
    }

    /**
     * Checks if the underlying data is empty
     *
     * @return boolean
     */
    public function isEmpty()
    {
        return empty($this->_data);
    }

    /**
     * Converts this object to an array
     * Note: Simply returns the underlying hash table
     *
     * @return array
     */
    public function toArray()
    {
        return $this->_data;
    }

    /**
     * Converts this object to a JSON string
     * Note: Simply calls json_encode on the hash table
     *
     * @return array
     */
    public function toJSON()
    {
        return json_encode($this->_data);
    }

    /**
     * Generic exception thrower
     *
     * @param string $exception
     * @param string $msg
     * @throws mixed
     */
    private function _throwException($exception, $msg)
    {
        $className = get_class($this);
        throw new $exception(sprintf($msg, $className));
    }

    /**
     * Internal chainable setter
     *
     * @param string $camelized
     * @param mixed $value
     * @return Object
     */
    protected function _set($camelized, $value)
    {
        $this->_data[$camelized] = $value;
        return $this;
    }

    /**
     * The fallback to be used with __call could not resolve.
     *
     * @param string $prefix
     * @param string $camelized
     * @param array $arguments
     * @throws \BadMethodCallException
     */
    protected function _defaultMethod($prefix, $camelized, $arguments)
    {
        $this->_throwException("\\BadMethodCallException", "Unsupport method $prefix $camelized in %s");
    }

    /**
     * Returns a tuple of a prefixed camel-case name, ie:
     * $this->_camelizedValue('addSomething') == array('add', 'something')
     *
     * @param string $name
     * @return array($prefixed, $camelized)
     */
    protected function _camelizedValue($name)
    {
        if (preg_match('/^([^A-Z0-9]+).+/', $name, $match)) {
            $modified = $this->_stripAndLower($name, strlen($match[1]));
            return array($match[1], $this->_underscore ? Utils::underscore($modified) : $modified);
        }
        return array("", "");
    }

    /**
     * Strips any prefix length, and lower cases the name.
     *
     * @param string $name
     * @param int $length
     * @return string
     */
    protected function _stripAndLower($name, $length)
    {
        $value = substr($name, $length);
        $value[0] = strtolower($value[0]);
        return $value;
    }

    /**
     * Serialized Object's should always be able to deserialize
     *
     * @return array
     */
    public function __sleep()
    {
        return array('_data');
    }
}

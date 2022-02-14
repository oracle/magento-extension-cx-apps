<?php

namespace Oracle\M2\Common\Serialize;

use Oracle\M2\Common\Utils;

/**
 * An exception representative of a serialization problem
 *
 * @author Philip Cali <philip.cali@oracle.com>
 */
class Exception extends \RuntimeException
{
    protected $_thing;
    protected $_input;
    protected $_encoding;

    /**
     * @param string $errorMessage
     * @param int $errorNo
     * @param mixed|string $thingOrInput
     * @param boolean $encoding
     */
    public function __construct($errorMessage, $errorNo, $thingOrInput, $encoding)
    {
        $this->_encoding = $encoding;
        if ($encoding) {
            $this->_thing = $thingOrInput;
        } else {
            $this->_input = $thingOrInput;
        }
        parent::__construct($this->__toString(), $errorNo);
    }

    /**
     * Did the exception occured while encoding an object
     *
     * @return boolean
     */
    public function isEncoding()
    {
        return $this->_encoding;
    }

    /**
     * Retrieves the object to be encoded
     *
     * @return mixed
     */
    public function getThing()
    {
        return $this->_thing;
    }

    /**
     * Retrieves the input to be decoded
     *
     * @return string
     */
    public function getInput()
    {
        return $this->_input;
    }
    
    /**
     * @see parent
     */
    public function __toString()
    {
        $payloadMessage = "Error occurred while " . (($this->_encoding) ?
            "encoding: " . Utils::stringify($this->_thing) : "decoding: " . Utils::stringify($this->_input));
        return parent::__toString() . PHP_EOL . $payloadMessage;
    }
}

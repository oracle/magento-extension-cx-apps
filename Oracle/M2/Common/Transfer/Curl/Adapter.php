<?php

namespace Oracle\M2\Common\Transfer\Curl;

/**
 * Implemented transfer adapter that handles cURL requests
 *
 * @author Philip Cali <philip.cali@oracle.com>
 */
class Adapter implements \Oracle\M2\Common\Transfer\Adapter
{
    protected $_options;

    /**
     * Set any additional cURL parameters in the option collection
     *
     * @param mixed $options
     */
    public function __construct($options = array())
    {
        if (is_array($options)) {
            $this->_options = new \Oracle\M2\Common\DataObject($options);
        } else {
            $this->_options = $options;
        }
    }

    /**
     * @see parent
     */
    public function createRequest($method, $uri)
    {
        return new Request($method, $uri, $this->_options);
    }
}

<?php

namespace Oracle\M2\Common\Transfer;

/**
 * Transfer related exceptions. May or may not
 * contain information about the request
 *
 * @author Philip Cali <philip.cali@oracle.com>
 */
class Exception extends \RuntimeException
{
    private $_request;

    /**
     * @see parent
     */
    public function __construct($message, $code, $request)
    {
        parent::__construct($message, $code);
        $this->_request = $request;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->_request;
    }
}

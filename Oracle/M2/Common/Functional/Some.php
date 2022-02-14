<?php

namespace Oracle\M2\Common\Functional;

/**
 * The Some type contains some kind of value
 *
 * @author Philip Cali <philip.cali@oracle.com>
 */
class Some extends Option
{
    private $_id;

    /**
     * Create the some with a contained value
     *
     * @param mixed $identity
     */
    public function __construct($identity)
    {
        $this->_id = $identity;
    }

    /**
     * @return boolean
     */
    public function isDefined()
    {
        return true;
    }

    /**
     * @return mixed
     */
    public function get()
    {
        return $this->_id;
    }
}

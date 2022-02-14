<?php

namespace Oracle\M2\Common\Serialize;

/**
 * Interface that defines an encoder
 *
 * @author Philip Cali <philip.cali@oracle.com>
 */
interface Encode
{
    /**
     * Encodes some value into a string state
     *
     * @param mixed $thing
     * @return string
     */
    public function encode($thing);
}

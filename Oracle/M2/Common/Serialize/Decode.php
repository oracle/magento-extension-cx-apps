<?php

namespace Oracle\M2\Common\Serialize;

/**
 * Interface that defines a decoder
 *
 * @author Philip Cali <philip.cali@oracle.com>
 */
interface Decode
{
    /**
     * Decodes a string into an associative state
     *
     * @param string $input
     * @return mixed
     */
    public function decode($input);
}

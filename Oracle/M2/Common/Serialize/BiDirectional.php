<?php

namespace Oracle\M2\Common\Serialize;

/**
 * Interface that represents a complete bi-directional
 * serailizer with associated MIME type.
 *
 * @author Philip Cali <philip.cali@oracle.com>
 */
interface BiDirectional extends Encode, Decode
{
    /**
     * Gets the MIME type for this bi-directional serializer
     *
     * @return string
     */
    public function getMimeType();
}

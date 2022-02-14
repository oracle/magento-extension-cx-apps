<?php

namespace Oracle\M2\Common\Transfer;

/**
 * Adapater factory to generate new transfer requests
 *
 * @author Philip Cali <philip.cali@oracle.com>
 */
interface Adapter
{
    /**
     * Initialize a request with a method and uri
     *
     * @param string $method
     * @param string $uri
     * @return Request
     */
    public function createRequest($method, $uri);
}

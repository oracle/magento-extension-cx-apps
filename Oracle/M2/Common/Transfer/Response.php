<?php

namespace Oracle\M2\Common\Transfer;

/**
 * Interface that defines a transfered response entity
 *
 * @author Philip Cali <philip.cali@oracle.com>
 */
interface Response
{
    const OK = 200;
    const CREATED = 201;
    const NO_CONTENT = 204;
    const BAD_REQUEST = 400;
    const UNAUTHORIZED = 401;
    const FORBIDDEN = 403;
    const NOT_FOUND = 404;
    const METHOD_NOT_ALLOWED = 405;
    const CONFLICT = 409;
    const INTERNAL_ERROR = 500;

    /**
     * Gets the response body as a string
     *
     * @return string
     */
    public function body();

    /**
     * Gets the response header as a string
     *
     * @param string $name
     * @return string
     */
    public function header($name);

    /**
     * Gets the response code as an integer
     *
     * @return int
     */
    public function code();
}

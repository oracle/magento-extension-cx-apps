<?php

namespace Oracle\M2\Common\Transfer;

/**
 * Enum for common HTTP headers
 *
 * @author Philip Cali <philip.cali@oracle.com>
 */
interface Headers
{
    const ACCEPT = 'Accept';
    const APPLICATION_JSON = 'application/json';
    const APPLICATION_FORM_URLENCODED = 'application/x-www-form-urlencoded';
    const AUTHORIZATION = 'Authorization';
    const CONTENT_TYPE = 'Content-Type';
}

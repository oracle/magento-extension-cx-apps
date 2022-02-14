<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Impl;

class CurlAdapter
{
    /**
     * Magento DI has some really WTF moments sometimes
     */
    public function aroundCreateRequest($subject, $createReq, $method, $uri)
    {
        return new \Oracle\M2\Common\Transfer\Curl\Request($method, $uri, new \Oracle\M2\Common\DataObject());
    }
}

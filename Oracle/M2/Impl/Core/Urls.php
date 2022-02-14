<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Impl\Core;

class Urls implements \Oracle\M2\Core\Store\UrlManagerInterface
{
    protected $_frontendUrl;

    /**
     * @param \Magento\Framework\Url $frontendUrl
     */
    public function __construct(
        \Magento\Framework\Url $frontendUrl
    ) {
        $this->_frontendUrl = $frontendUrl;
    }

    /**
     * @see parent
     */
    public function getFrontendUrl($store, $path, $params = [])
    {
        return $this->_frontendUrl->setScope($store)->getUrl($path, $params);
    }
}

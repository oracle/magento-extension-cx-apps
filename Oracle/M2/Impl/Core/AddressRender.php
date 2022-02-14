<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Impl\Core;

class AddressRender implements \Oracle\M2\Core\Sales\AddressRenderInterface
{
    protected $_render;

    /**
     * @param \Magento\Sales\Model\Order\Address\Renderer $render
     */
    public function __construct(
        \Magento\Sales\Model\Order\Address\Renderer $render
    ) {
        $this->_render = $render;
    }

    /**
     * @see parent
     */
    public function format($address, $type)
    {
        return $this->_render->format($address, $type);
    }
}

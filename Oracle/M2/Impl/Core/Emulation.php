<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Impl\Core;

class Emulation implements \Oracle\M2\Core\App\EmulationInterface
{
    protected $_emulation;

    /**
     * @param \Magento\Store\Model\App\Emulation $appEmulation
     */
    public function __construct(
        \Magento\Store\Model\App\Emulation $appEmulation
    ) {
        $this->_emulation = $appEmulation;
    }

    /**
     * @see parent
     */
    public function startEnvironmentEmulation($storeId, $area, $force)
    {
        $this->_emulation->startEnvironmentEmulation($storeId, $area, $force);
    }

    /**
     * @see parent
     */
    public function stopEnvironmentEmulation()
    {
        $this->_emulation->stopEnvironmentEmulation();
    }
}

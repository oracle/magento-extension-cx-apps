<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Impl\Core;

class OrderStatuses implements \Oracle\M2\Core\Sales\OrderStatusesInterface
{
    protected $_orderConfig;
    protected $_statuses;

    /**
     * @param \Magento\Sales\Model\Order\Config $orderConfig
     */
    public function __construct(
        \Magento\Sales\Model\Order\Config $orderConfig
    ) {
        $this->_orderConfig = $orderConfig;
    }

    /**
     * @see parent
     */
    public function getOptionArray()
    {
        if (is_null($this->_statuses)) {
            $this->_statuses = [];
            foreach ($this->_orderConfig->getStatuses() as $code => $label) {
                $this->_statuses[] = [ 'id' => $code, 'name' => $label ];
            }
        }
        return $this->_statuses;
    }
}

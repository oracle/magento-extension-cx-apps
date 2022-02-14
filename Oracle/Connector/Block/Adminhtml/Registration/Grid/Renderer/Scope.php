<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Connector\Block\Adminhtml\Registration\Grid\Renderer;

class Scope extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    private $_middleware;

    /**
     * @param \Oracle\Connector\Model\MiddlewareInterface $middleware
     */
    public function __construct(
        \Oracle\M2\Connector\MiddlewareInterface $middleware
    ) {
        $this->_middleware = $middleware;
    }

    /**
     * Gets the scope display name for the registration scope
     *
     * @param \Magento\Framework\DataObject $row
     * @return string
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        return $this->_printTree($this->_middleware->scopeTree($row));
    }

    /**
     * @param $tree
     * @param int $level
     * @return string
     */
    private function _printTree($tree, $level = 0)
    {
        $html = '';
        if ($level > 0) {
            $spaces = implode(array_fill(0, $level, '&nbsp;'));
            $html .= "<br>$spaces";
        }
        $html .= "<span>{$tree['name']}";
        if (!empty($tree['children'])) {
            foreach ($tree['children'] as $child) {
                $html .= $this->_printTree($child, $level + 2);
            }
        }
        return $html . '</span>';
    }
}

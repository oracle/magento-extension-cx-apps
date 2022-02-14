<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Connector\Plugin;

class Allow
{
    const MODULE_NAME = 'oracle';

    protected $_allowedActions = [
        'discovery',
        'health',
        'endpoint',
        'settings',
        'source',
        'scope',
        'script',
        'trigger',
        'redirect',
    ];

    /**
     * @see parent
     */
    public function aroundDispatch(
        \Magento\Backend\App\AbstractAction $subject,
        \Closure $proceed,
        \Magento\Framework\App\RequestInterface $request
    ) {
        $actionName = $request->getActionName();
        $moduleName = $request->getModuleName();
        if ($moduleName == self::MODULE_NAME && in_array($actionName, $this->_allowedActions)) {
            // This bypasses user authentication for the Middleware
            return $subject->execute();
        } else {
            return $proceed($request);
        }
    }
}

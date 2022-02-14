<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Connector\Controller\Site;

class Fiddle extends \Magento\Framework\App\Action\Action
{
    const FIDDLE_EVENT = 'oracle_site_fiddle';

    /**
     * @see parent
     */
    public function execute()
    {
        $this->_eventManager->dispatch(self::FIDDLE_EVENT, [
            'request' => $this->getRequest()
        ]);
        return $this->getResponse();
    }
}

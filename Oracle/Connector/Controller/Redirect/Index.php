<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Connector\Controller\Redirect;

class Index extends \Magento\Framework\App\Action\Action
{
    const REDIRECT_MODEL = 'Oracle\M2\Connector\Redirect';
    const REDIRECT_PARAM = "redirect_path";
    const SERVICE_PARAM = "service";

    /**
     * @see parent
     */
    public function execute()
    {
        $serviceName = $this->getRequest()->getParam(self::SERVICE_PARAM);
        $redirectPath = $this->getRequest()->getParam(self::REDIRECT_PARAM);

        $redirecter = $this->_objectManager->create(self::REDIRECT_MODEL);
        $redirecter->setPath($redirectPath);
        $allParams = $this->getRequest()->getParams();
        foreach ($allParams as $key => $value) {
            if ($key == self::REDIRECT_PARAM || $key == self::SERVICE_PARAM) {
                continue;
            }
            $redirecter->setParam($key, $value);
        }

        if (!empty($serviceName)) {
            $areaName = "oracle_connector_redirect_{$serviceName}";
            $this->_eventManager->dispatch($areaName, [
                'redirect' => $redirecter,
                'request' => $this->getRequest(),
                'messages' => $this->messageManager
            ]);
        }

        if ($redirecter->getIsReferer()) {
            $response = $this->getResponse();
            $response->setRedirect($response->getRefererUrl());
            return $response;
        } else {
            $redirectPath = $redirecter->getPath();
            if (empty($redirectPath)) {
                $redirectPath = '/';
            }
            if (preg_match('/^http/', $redirectPath)) {
                $query = $this->getRequest()->getServer('QUERY_STRING', '');
                if (!empty($query)) {
                    $redirectPath .= '?' . $query;
                }
                return $this->getResponse()->setRedirect($redirectPath);
            } else {
                return $this->_redirect($redirectPath, $redirecter->getParams());
            }
        }
    }
}

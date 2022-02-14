<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Coupon;

interface SettingsInterface extends \Oracle\M2\Connector\Event\HelperInterface
{
    const XML_PATH_ENABLED = 'oracle/coupon/extensions/settings/enabled';
    const XML_PATH_MESSAGE = 'oracle/coupon/extensions/settings/%s_message';
    const XML_PATH_LINK_CONTENT = 'oracle/coupon/extensions/settings/link_text';
    const XML_PATH_COUPON_PARAM = 'oracle/coupon/extensions/settings/coupon_param';
    const XML_PATH_INVALID_PARAM = 'oracle/coupon/extensions/settings/invalid_param';
    const INVALID_CODE = 'invalid';
    const DEPLETED_CODE = 'depleted';
    const EXPIRED_CODE = 'expired';
    const CONFLICT_CODE = 'conflict';
    const FORCE_PARAM = '___force_code';

    /**
     * Is this request a forced application
     *
     * @return boolean
     */
    public function isForced();

    /**
     * Get the params for the store
     *
     * @param string $scopeType
     * @param mixed $scopeId
     * @return array
     */
    public function getParams($scopeType = 'default', $scopeId = null);

    /**
     * Determines if the extension should display a message
     *
     * @param string $scopeType
     * @param mixed $scopeId
     * @return boolean
     */
    public function isDisplayMessage($scopeType = 'default', $scopeId = null);

    /**
     * Gets the link content in case of a conflict
     *
     * @param string $scopeType
     * @param mixed $scopeId
     * @return string
     */
    public function getLinkContent($scopeType = 'default', $scopeId = null);

    /**
     * Applies the coupon code from a request
     *
     * @param mixed $messages
     * @param mixed $store
     * @return boolean
     */
    public function applyCodeFromRequest($messages, $store);

    /**
     * Applies the coupon code from a rule or session
     *
     * @param mixed $ruleId
     * @param string $couponCode
     * @return void
     */
    public function applyCode($ruleId = null, $couponCode = null);
}

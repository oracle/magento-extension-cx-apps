<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Integration;

interface CartSettingsInterface
{
    const XML_PATH_RECOVERY_ENABLED = 'oracle/integration/extensions/cart_recovery/enabled';
    const XML_PATH_RECOVERY_EMBED = 'oracle/integration/extensions/cart_recovery/embed_code';
    const XML_PATH_RECOVERY_TAX_INCLUDED = 'oracle/integration/extensions/cart_recovery/tax_included';

    /**
     * Is Cart Recovery enabled?
     *
     * @param string $scopeType
     * @param mixed $scopeId
     * @return boolean
     */
    public function isCartRecoveryEnabled($scopeType = 'default', $scopeId = null);

    /**
     * Gets the pasted cart recovery embed code
     *
     * @param string $scopeType
     * @param mixed $scopeId
     * @return string
     */
    public function getCartRecoveryEmbedCode($scopeType = 'default', $scopeId = null);

    /**
     * Based on the saved cart, get email address for recovery
     *
     * @param mixed $quote
     * @return string
     */
    public function getCartRecoveryEmail($quote);

    /**
     * Gets an appropriate redirect for loading the model's page
     *
     * @param mixed $modelId
     * @param mixed $store
     * @param string $modelType
     */
    public function getRedirectUrl($modelId, $store, $modelType = 'cart');

    /**
     * Determines if the shadow dom should be written
     *
     * @param string $scopeType
     * @param mixed $scopeId
     * @return boolean
     */
    public function isShadowDom($scopeType = 'default', $scopeId = null);

    /**
     * Determines whether or not to include tax with price values
     *
     * @param string $scopeType
     * @param int $scopeId [null]
     * @return boolean
     */
    public function isTaxIncluded($scopeType = 'default', $scopeId = null);
}

<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Optin\Block\Checkout;

class LayoutProcessor implements \Magento\Checkout\Block\Checkout\LayoutProcessorInterface
{
    protected $_settings;
    protected $_checkout;
    protected $_session;
    protected $_storeManager;

    const PREFERENCE_SHIPPING = 'shipping';
    const PREFERENCE_REVIEW = 'review';

    const NEWSLETTER_SHIPPING_TEMPLATE = 'Oracle_Optin/shipping/newsletter';
    const NEWSLETTER_REVIEW_TEMPLATE = 'Oracle_Optin/review/newsletter';

    /**
     * @param \Oracle\M2\Optin\SettingsInterface $settings
     * @param \Oracle\M2\Optin\Checkout $checkout
     * @param \Magento\Customer\Model\Session $session
     * @param \Oracle\M2\Core\Store\ManagerInterface $storeManager
     */
    public function __construct(
        \Oracle\M2\Optin\SettingsInterface $settings,
        \Oracle\M2\Optin\Checkout $checkout,
        \Magento\Customer\Model\Session $session,
        \Oracle\M2\Core\Store\ManagerInterface $storeManager
    ) {
        $this->_settings = $settings;
        $this->_checkout = $checkout;
        $this->_session = $session;
        $this->_storeManager = $storeManager;
    }

    /**
     * @see parent
     */
    public function process($jsLayout)
    {
        $store = $this->_storeManager->getStore();
        if ($this->_settings->isCheckoutEnabled('store', $store)) {
            $layout = $this->_settings->getCheckoutLayout('store', $store);
            switch ($layout) {
                case self::PREFERENCE_SHIPPING:
                    $fields = [];
                    if (isset($jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shippingAdditional']['children'])) {
                        $fields =& $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shippingAdditional']['children'];
                    }
                    if (!empty($fields)) {
                        $this->_applyFieldChange($store, 'shippingAddress', $fields, self::NEWSLETTER_SHIPPING_TEMPLATE);
                    }
                    break;
                case self::PREFERENCE_REVIEW:
                    if (isset($jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['afterMethods']['children'])) {
                        $fields =& $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['afterMethods']['children'];
                        $this->_applyFieldChange($store, 'payment', $fields, self::NEWSLETTER_REVIEW_TEMPLATE);
                    }
                    break;
            }
        }
        return $jsLayout;
    }

    /**
     * Applies the checkout, checkbox change to the fields
     *
     * @param mixed $store
     * @param string $dataPrefix
     * @param array $fields
     * @param string $template Path to newsletter template
     * @return void
     */
    protected function _applyFieldChange($store, $dataPrefix, &$fields, $template)
    {
        $customer = null;
        if ($this->_session->isLoggedIn()) {
            $customer = $this->_session->getCustomer();
        }
        $visible = $this->_checkout->isCheckboxVisible($store, $customer);
        $fieldName = 'subscribe_to_newsletter';
        if ($visible && isset($fields[$fieldName])) {
            $fields[$fieldName] = [
                'component' => 'Oracle_Optin/js/form/element/boolean',
                'config' => [
                    'customScope' => $dataPrefix,
                    'template' => $template,
                    'elementTmpl' => 'ui/form/element/checkbox',
                ],
                'value' => $this->_settings->isCheckedByDefault('store', $store) ? 1 : 0,
                'dataScope' => $dataPrefix . '.' . $fieldName,
                'description' => __($this->_settings->getCheckoutLabel('store', $store)),
                'provider' => 'checkoutProvider',
                'sortOrder' => $fields[$fieldName]['sortOrder'],
                'visible' => $visible
            ];
        }
    }
}

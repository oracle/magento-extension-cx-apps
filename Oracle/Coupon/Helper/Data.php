<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Coupon\Helper;

use \Magento\SalesRule\Model\Rule;

class Data extends \Magento\Framework\App\Helper\AbstractHelper implements \Oracle\M2\Coupon\SettingsInterface
{
    protected static $_validErrorCodes = [
        self::INVALID_CODE => 'translateCode',
        self::DEPLETED_CODE => 'translateCode',
        self::EXPIRED_CODE => 'translateCode',
        self::CONFLICT_CODE => 'translateConflict'
    ];

    protected $_checkoutSession;
    protected $_quoteRepository;
    protected $_customerSession;
    protected $_session;
    protected $_coupons;
    protected $_rules;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Quote\Model\QuoteRepository $quoteRepository
     * @param \Magento\Framework\Session\SessionManagerInterface $session
     * @param \Magento\SalesRule\Model\CouponFactory $coupons
     * @param \Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory $rules
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Quote\Model\QuoteRepository $quoteRepository,
        \Magento\Framework\Session\SessionManagerInterface $session,
        \Magento\SalesRule\Model\CouponFactory $coupons,
        \Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory $rules
    ) {
        parent::__construct($context);
        $this->_checkoutSession = $checkoutSession;
        $this->_customerSession = $customerSession;
        $this->_quoteRepository = $quoteRepository;
        $this->_session = $session;
        $this->_coupons = $coupons;
        $this->_rules = $rules;
    }

    /**
     * @see parent
     */
    public function isForced()
    {
        return $this->_request->has(self::FORCE_PARAM);
    }

    /**
     * @see parent
     */
    public function isEnabled($scopeType = 'default', $scopeId = null)
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ENABLED, $scopeType, $scopeId);
    }

    /**
     * @see parent
     */
    public function getParams($scopeType = 'default', $scopeId = null)
    {
        return [
            $this->scopeConfig->getValue(self::XML_PATH_COUPON_PARAM, $scopeType, $scopeId),
            $this->scopeConfig->getValue(self::XML_PATH_INVALID_PARAM, $scopeType, $scopeId)
        ];
    }

    /**
     * @see parent
     */
    public function isDisplayMessage($scopeType = 'default', $scopeId = null)
    {
        return $this->scopeConfig->isSetFlag(sprintf(self::XML_PATH_MESSAGE, 'display'), $scopeType, $scopeId);
    }

    /**
     * @see parent
     */
    public function getLinkContent($scopeType = 'default', $scopeId = null)
    {
        return $this->scopeConfig->getValue(self::XML_PATH_LINK_CONTENT, $scopeType, $scopeId);
    }

    /**
     * Entry point to apply a coupon code to a shopping session
     *
     * @param \Magento\Framework\App\Message\ManagerInterface $messages
     * @param \Magento\Store\Model\Store $store
     * @return boolean
     */
    public function applyCodeFromRequest($messages, $store)
    {
        list($couponParam, $errorParam) = $this->getParams('store', $store);
        $errorCode = $this->_request->getParam($errorParam);
        $couponCode = $this->_request->getParam($couponParam);
        $isDisplay = $this->isDisplayMessage('store', $store);
        if ($errorCode || $couponCode) {
            if (!empty($couponCode)) {
                $force = $this->_request->has(self::FORCE_PARAM);
                try {
                    $coupon = $this->_validateCode($couponCode, $force, $store);
                    if (!$this->_isCouponApplied($coupon->getRuleId(), $couponCode)) {
                        $this->applyCode($coupon->getRuleId(), $couponCode);
                        if ($isDisplay) {
                            $messages->addSuccess($this->_successMessage($couponCode, $store));
                        }
                    }
                    return true;
                } catch (\Exception $e) {
                    $errorCode = $e->getMessage();
                }
            }
            if (!$this->_isValidErrorCode($errorCode)) {
                $errorCode = self::INVALID_CODE;
            }
            if ($isDisplay) {
                $messages->addError($this->_errorMessage($errorCode, $couponCode, $store));
            }
        }
        return false;
    }

    /**
     * Applies the code to the session
     *
     * @param mixed $ruleId
     * @param string $couponCode
     * @return void
     */
    public function applyCode($ruleId = null, $couponCode = null)
    {
        if (is_null($couponCode)) {
            $couponCode = $this->_session->getCouponCode();
            $ruleId = $this->_session->getRuleId();
        } else {
            $this->_session->setCouponCode($couponCode);
            $this->_session->setRuleId($ruleId);
        }
        $quote = $this->_checkoutSession->getQuote();
        if ($quote && $couponCode) {
            $quote->getShippingAddress()->setCollectShippingRates(true);
            $quote->setCouponCode($couponCode)->collectTotals();
            $this->_quoteRepository->save($quote);
            if ($this->_isRuleApplied($ruleId)) {
                $this->_session->unsCouponCode($couponCode);
                $this->_session->unsRuleId($ruleId);
            }
        }
    }

    /**
     * Retrieves the message content from config
     *
     * @param string $errorCode
     * @param string $couponCode
     * @param mixed $storeId
     * @return string
     */
    protected function _errorMessage($errorCode, $couponCode, $storeId = null)
    {
        $message = $this->scopeConfig->getValue(sprintf(self::XML_PATH_MESSAGE, $errorCode), 'store', $storeId);
        $translateCallback = [$this, self::$_validErrorCodes[$errorCode]];
        return call_user_func($translateCallback, $message, empty($couponCode) ? 'code' : $couponCode, $storeId);
    }

    /**
     * Retrieves the success message with code replacement
     *
     * @param string $couponCode
     * @param mixed $storeId
     * @return string
     */
    protected function _successMessage($couponCode, $storeId = null)
    {
        $message = $this->scopeConfig->getValue(sprintf(self::XML_PATH_MESSAGE, 'success'), 'store', $storeId);
        return $this->translateCode($message, $couponCode, $storeId);
    }

    /**
     * Is the error code a valid message code?
     *
     * @param string $code
     * @return boolean
     */
    protected function _isValidErrorCode($code)
    {
        return array_key_exists($code, self::$_validErrorCodes);
    }

    /**
     * Translate a coupon code in the text
     *
     * @param string $message
     * @param string $couponCode
     * @param string $key
     * @return string
     */
    protected function translateCode($message, $couponCode, $store, $key = 'code')
    {
        return __(str_replace('{' . $key . '}', $couponCode, $message));
    }

    /**
     * Translate the conflict text
     *
     * @param string $message
     * @param string $couponCode
     * @param mixed $store
     * @return string
     */
    protected function translateConflict($message, $couponCode, $store)
    {
        list($couponParam, $invalidParam) = $this->getParams('store', $store);
        $forceUrl = $store->getUrl('*/*/*', [
            $couponParam => $couponCode,
            self::FORCE_PARAM => 1,
        ]);
        $quote = $this->_checkoutSession->getQuote();
        $linkContent = $this->getLinkContent('store', $store);
        $replacements = [
            'link' => '<a href="' . $forceUrl . '">' . $linkContent . '</a>',
            'oldCode' => $quote->getCouponCode(),
            'newCode' => $couponCode,
        ];
        foreach ($replacements as $key => $value) {
            $message = str_replace('{' . $key . '}', $value, $message);
        }
        return __($message);
    }

    /**
     * Validates the shopping price rule
     *
     * @param string $couponCode
     * @param boolean $force
     * @return mixed
     */
    protected function _validateCode($couponCode, $force = false, $store)
    {
        $websiteId = $store->getWebsiteId();
        $customerGroupId = $this->_customerSession->getCustomerGroupId();
        $validTypes = [Rule::COUPON_TYPE_SPECIFIC, Rule::COUPON_TYPE_AUTO];
        $rules = $this->_rules->create()
            ->setValidationFilter($websiteId, $customerGroupId, $couponCode)
            ->addFieldToFilter('main_table.coupon_type', ['in' => $validTypes]);
        if ($rules->getSize()) {
            $coupon = $this->_coupons->create()->loadByCode($couponCode);
            if ($coupon->getUsageLimit() && $coupon->getTimesUsed() >= $coupon->getUsageLimit()) {
                throw new \RuntimeException('depleted');
            }
            $quote = $this->_checkoutSession->getQuote();
            if ($quote) {
                if (!$force && $quote->getCouponCode() && $quote->getCouponCode() != $couponCode) {
                    throw new \RuntimeException('conflict');
                }
            }
            return $coupon;
        }
        throw new \RuntimeException('invalid');
    }

    /**
     * Checks if the rule has been aplpied to the cart
     *
     * @param mixed $ruleId
     * @return boolean
     */
    protected function _isRuleApplied($ruleId)
    {
        $quote = $this->_checkoutSession->getQuote();
        if ($quote) {
            return in_array($ruleId, explode(',', $quote->getAppliedRuleIds()));
        }
        return false;
    }

    /**
     * Checks if the coupon code has been applied to the session
     *
     * @param mixed $ruleId
     * @param string $couponCode
     * @return boolean
     */
    protected function _isCouponApplied($ruleId, $couponCode)
    {
        if (!$this->_isRuleApplied($ruleId)) {
            return false;
        }
        return $this->_session->getCouponCode() == $couponCode;
    }
}

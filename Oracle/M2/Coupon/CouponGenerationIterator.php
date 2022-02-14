<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Coupon;

class CouponGenerationIterator implements \Iterator
{
    protected $_rules;
    protected $_middleware;
    protected $_generators;
    protected $_currentCoupons;
    protected $_startTime;
    protected $_endtime;
    protected $_codePrefix;
    protected $_codeSuffix;
    protected $_limit;
    protected $_offset;

    /**
     * @param \Oracle\M2\Connector\MiddlewareInterface $middleware
     * @param \Oracle\M2\Core\Sales\RuleManagerInterface $rules
     * @param array $generators
     * @param mixed $startTime
     * @param mixed $endTime
     * @param mixed $codePrefix
     * @param mixed $codeSuffix
     */
    public function __construct(
        \Oracle\M2\Connector\MiddlewareInterface $middleware,
        \Oracle\M2\Core\Sales\RuleManagerInterface $rules,
        array $generators,
        $startTime = null,
        $endTime = null,
        $codePrefix = null,
        $codeSuffix = null,
        $limit = 20,
        $offset = 0
    ) {
        $this->_middleware = $middleware;
        $this->_rules = $rules;
        $this->_startTime = $startTime;
        $this->_endtime = $endTime;
        $this->_codePrefix = $codePrefix;
        $this->_codeSuffix = $codeSuffix;
        $this->_limit = $limit;
        $this->_offset = $offset;
        $this->_generators = new \ArrayIterator($generators);
    }

    /**
     * @see parent
     */
    public function current()
    {
        $generator = $this->_generators->current();
        $storeId = $this->_middleware->defaultStoreId($generator['scope'], $generator['scopeId']);
        return new \Oracle\M2\Common\DataObject(
            [
                'storeId' => $storeId,
                'ruleId' => $generator['ruleId'],
                'campaignId' => $generator['campaignId'],
                'coupons' => [ $this->_currentCoupons->current()->getCode() ]
            ]
        );
    }

    /**
     * @see parent
     */
    public function key()
    {
        return $this->_currentCoupons->key();
    }

    /**
     * @see parent
     */
    public function next()
    {
        $this->_currentCoupons->next();
    }

    /**
     * @see parent
     */
    public function rewind()
    {
        $this->_generators->rewind();
    }

    /**
     * Sets the current limit for the coupons
     *
     * @param int $limit
     * @return $this
     */
    public function setLimit($limit)
    {
        $this->_limit = $limit;
        return $this;
    }

    /**
     * Sets the current offset for the coupons
     *
     * @param int $offset
     * @return $this
     */
    public function setOffset($offset)
    {
        $this->_offset = $offset;
        return $this;
    }

    /**
     * @see parent
     */
    public function valid()
    {
        if (is_null($this->_currentCoupons) && $this->_generators->valid()) {
            $this->_unusedCoupons();
        } elseif (is_null($this->_currentCoupons) && !$this->_generators->valid()) {
            return false;
        } elseif (!$this->_currentCoupons->valid() && $this->_generators->valid()) {
            $this->_generators->next();
            $this->_unusedCoupons();
        }
        return $this->_currentCoupons->valid();
    }

    /**
     * Fill current coupons with with current generator
     *
     * @return void
     */
    protected function _unusedCoupons()
    {
        $generator = $this->_generators->current();
        if (!is_null($generator)) {
            $this->_currentCoupons = $this->_rules->unusedCoupons(
                $generator['ruleId'],
                $this->_startTime,
                $this->_endtime,
                $this->_codePrefix,
                $this->_codeSuffix,
                $this->_limit,
                $this->_offset
            );
        }
    }
}

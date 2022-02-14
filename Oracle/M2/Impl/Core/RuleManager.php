<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Impl\Core;

class RuleManager implements \Oracle\M2\Core\Sales\RuleManagerInterface
{
    protected $_ruleFactory;
    protected $_ruleData;
    protected $_couponData;
    protected $_generatorFactory;
    protected $_searchBuilder;

    /**
     * @param \Magento\SalesRule\Model\RuleFactory $ruleFactory
     * @param \Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory $ruleData
     * @param \Magento\SalesRule\Api\CouponRepositoryInterface $couponData
     * @param \Magento\SalesRule\Model\Coupon\MassgeneratorFactory $generatorFactory
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchBuilder
     */
    public function __construct(
        \Magento\SalesRule\Model\RuleFactory $ruleFactory,
        \Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory $ruleData,
        \Magento\SalesRule\Api\CouponRepositoryInterface $couponData,
        \Magento\SalesRule\Model\Coupon\MassgeneratorFactory $generatorFactory,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchBuilder
    ) {
        $this->_ruleFactory = $ruleFactory;
        $this->_ruleData = $ruleData;
        $this->_couponData = $couponData;
        $this->_generatorFactory = $generatorFactory;
        $this->_searchBuilder = $searchBuilder;
    }

    /**
     * @see parent
     */
    public function getBySource(\Oracle\M2\Connector\Discovery\Source $source, $onlyPools = true)
    {
        $collection = $this->_ruleData->create();
        if ($onlyPools) {
            $collection
                ->addFieldToFilter([ 'coupon_type', 'use_auto_generation' ], [
                      [ 'eq' => 3 ],
                      [ 'eq' => 1 ]
                  ]);
        } else {
            $collection
                ->addFieldToFilter('coupon_type', [ 'eq' => 2 ])
                ->addFieldToFilter('use_auto_generation', [ 'eq' => 0 ]);
        }
        foreach ($source->getFilters() as $field => $value) {
            $collection->addFieldToFilter($field, [ 'like' => "%$value%" ]);
        }
        if ($source->getId()) {
            $collection->addFieldToFilter('rule_id', [ 'eq' => $source->getId() ]);
        }
        $collection
            ->getSelect()
            ->limitPage($source->getOffset(), $source->getLimit());
        return $collection;
    }

    /**
     * @see parent
     */
    public function getById($ruleId)
    {
        try {
            $rule = $this->_ruleFactory->create()->load($ruleId);
            return $rule->getId() ? $rule : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @see parent
     */
    public function isReplenishable($data)
    {
        $coupons = $this->unusedCoupons($data['ruleId'])->getSize();
        return $coupons == 0;
    }

    /**
     * @see parent
     */
    public function acquireCoupons($data)
    {
        $generator = $this->_generatorFactory->create();
        $generator->setData($data);
        return $generator->generatePool()->getGeneratedCodes();
    }

    /**
     * @see parent
     */
    public function unusedCoupons($ruleId, $startTime = null, $endTime = null, $codePrefix = null, $codeSuffix = null, $limit = 20, $offset = 0)
    {
        $this->_searchBuilder->addFilter('rule_id', $ruleId, 'eq');
        if (!is_null($startTime)) {
            $this->_searchBuilder->addFilter('created_at', $startTime, 'gt');
        }
        if (!is_null($endTime)) {
            $this->_searchBuilder->addFilter('created_at', $startTime, 'lt');
        }
        if (!is_null($codePrefix)) {
            $this->_searchBuilder->addFilter('code', "{$codePrefix}%", 'like');
        }
        if (!is_null($codeSuffix)) {
            $this->_searchBuilder->addFilter('code', "%{$codeSuffix}", 'like');
        }
        $this->_searchBuilder->addFilter('times_used', 0, 'eq');
        $this->_searchBuilder->setPageSize($limit)->setCurrentPage($offset / $limit);
        return new \ArrayIterator($this->_couponData->getList($this->_searchBuilder->create())->getItems());
    }
}

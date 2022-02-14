<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Core\Sales;

interface RuleManagerInterface
{
    /**
     * Gets a collection of rules determined by the source request
     *
     * @param \Oracle\M2\Connector\Discovery\Source $source
     * @param boolean $onlyPools
     * @return \Iterator
     */
    public function getBySource(\Oracle\M2\Connector\Discovery\Source $source, $onlyPools = true);

    /**
     * Gets a coupon rule by its ID
     *
     * @param mixed $ruleId
     * @return mixed
     */
    public function getById($ruleId);

    /**
     * Provided generator data, determine if the associated pool can
     * be replenished
     *
     * @param array $data
     * @return boolean
     */
    public function isReplenishable($data);

    /**
     * Uses a generation data defined in Connector to generate some coupons
     *
     * @param array $data ['rule_id' => 'n', ... ]
     * @return array
     */
    public function acquireCoupons($data);

    /**
     * Stream all of the unused coupons for a pool
     *
     * @param mixed $ruleId
     * @param mixed $startTime
     * @param mixed $endTime
     * @param mixed $codePrefix
     * @param mixed $codeSuffix
     * @param int $limit
     * @param int $offset
     * @return \Iterator
     */
    public function unusedCoupons($ruleId, $startTime = null, $endTime = null, $codePrefix = null, $codeSuffix = null, $limit = 20, $offset = 0);
}

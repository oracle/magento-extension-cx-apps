<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Connector\Api;

use Oracle\Connector\Api\Data\TidInterface;
use Oracle\Connector\Model\ResourceModel\Tid\Collection;

/**
 * Interface TidRepositoryInterface
 *
 * TID CRUD Interface
 *
 * @api
 * @package Oracle\Connector\Api
 */
interface TidRepositoryInterface
{
    /**
     * @param string $value
     * @param int $cartId
     * @return TidInterface
     * @throws \DomainException if $value does not consist of only digits
     */
    public function create($value, $cartId);

    /**
     * @param TidInterface $tid
     */
    public function save(TidInterface $tid);

    /**
     * @param int $cartId
     * @return TidInterface
     */
    public function getByCartId($cartId);

    /**
     * @param int $orderId
     * @return TidInterface
     */
    public function getByOrderId($orderId);

    /**
     * @param array $orderIds
     * @return Collection
     */
    public function getByOrderIds(array $orderIds);

    /**
     * @param $tid
     * @return bool
     * @throws \Exception on failure to delete
     */
    public function delete(TidInterface $tid);
}
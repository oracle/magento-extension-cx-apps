<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Connector\Api\Data;

/**
 * Interface TidInterface
 * 
 * @api
 * @package Oracle\Connector\Api\Data
 */
interface TidInterface
{
    /**
     * @return int
     */
    public function getId();

    /**
     * @param int $id
     * @return self
     */
    public function setId($id);

    /**
     * @return int
     */
    public function getValue();

    /**
     * @param int $value
     * @return self
     * @throws \DomainException if value string contains characers other than digits
     */
    public function setValue($value);

    /**
     * @return int
     */
    public function getCartId();

    /**
     * @param int $id
     * @return self
     */
    public function setCartId($id);

    /**
     * @return int
     */
    public function getOrderId();

    /**
     * @param int $id
     * @return self
     */
    public function setOrderId($id);

    /**
     * @return string
     */
    public function getCreatedAt();

    /**
     * @param string $id
     * @return self
     */
    public function setCreatedAt($id);
}
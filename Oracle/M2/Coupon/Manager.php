<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Coupon;

class Manager extends \Oracle\M2\Core\Config\ContainerAbstract implements ManagerInterface
{
    protected $_data;
    protected $_writer;
    protected $_rules;
    protected $_config;

    /**
     * @param \Oracle\M2\Core\Config\ScopedInterface $config,
     * @param \Oracle\M2\Core\Config\FactoryInterface $data,
     * @param \Oracle\M2\Core\Sales\RuleManagerInterface $rules
     */
    public function __construct(
        \Oracle\M2\Core\Config\ScopedInterface $config,
        \Oracle\M2\Core\Config\FactoryInterface $data,
        \Oracle\M2\Core\Config\ManagerInterface $writer,
        \Oracle\M2\Core\Sales\RuleManagerInterface $rules
    ) {
        parent::__construct($config);
        $this->_data = $data;
        $this->_rules = $rules;
        $this->_writer = $writer;
    }

    /**
     * @see parent
     */
    public function getAll(\Oracle\M2\Connector\RegistrationInterface $registration)
    {
        $scopeType = $registration->getScope();
        if ($scopeType != 'default') {
            $scopeType .= 's';
        }
        $data = $this->_data->getCollection()
            ->addFieldToFilter('path', ['like' => self::XML_PATH_OBJECT_PATH])
            ->addFieldToFilter('scope', ['eq' => $scopeType])
            ->addFieldToFilter('scope_id', ['eq' => $registration->getScopeId()]);
        $generators = [];
        foreach ($data as $config) {
            $object = unserialize($config->getValue());
            if (!$this->_validate($object)) {
                continue;
            }
            $object['scope'] = $config->getScope();
            $object['scopeId'] = $config->getScopeId();
            $generators[] = $object;
        }
        return $generators;
    }

    /**
     * @see parent
     */
    public function getById($generatorId, $force = false)
    {
        $objectId = str_replace('%', $this->_safeId($generatorId), self::XML_PATH_OBJECT_PATH);
        $data = $this->_data->getCollection()
            ->addFieldToFilter('path', ['eq' => $objectId]);
        $object = null;
        $config = null;
        foreach ($data as $config) {
            $object = $config->getValue();
        }
        if (empty($object)) {
            return null;
        }
        $object = unserialize($object);
        if (!$this->_validate($object, $force)) {
            return null;
        }
        $object['scope'] = $config->getScope();
        $object['scopeId'] = $config->getScopeId();
        return $object;
    }

    /**
     * @see parent
     */
    public function save($generatorId, $generator, \Oracle\M2\Connector\RegistrationInterface $registration)
    {
        $objectId = str_replace('%', $this->_safeId($generatorId), self::XML_PATH_OBJECT_PATH);
        $this->_writer->save($objectId, serialize($generator), $registration->getScope(), $registration->getScopeId());
    }

    /**
     * @see parent
     */
    public function acquireCoupons($generator, $amount = null)
    {
        if (!$this->_validate($generator)) {
            return [];
        }
        if (is_null($amount)) {
            $amount = $generator['amount'];
        }
        return $this->_rules->acquireCoupons([
            'qty' => $amount,
            'rule_id' => $generator['ruleId'],
            'format' => $generator['format'],
            'length' => $generator['length'],
            'dash' => array_key_exists('dashInterval', $generator) ? $generator['dashInterval'] : 0,
            'prefix' => array_key_exists('prefix', $generator) ? $generator['prefix'] : null,
            'suffix' => array_key_exists('suffix', $generator) ? $generator['suffix'] : null
        ]);
    }

    /**
     * @see parent
     */
    public function acquireCoupon($generatorId)
    {
        $generator = $this->getById($generatorId);
        if (empty($generator)) {
            return '';
        }
        $coupons = $this->acquireCoupons($generator, 1);
        if (empty($coupons)) {
            return '';
        }
        return $coupons[0];
    }

    /**
     * @see parent
     */
    public function getReplenishablePoolIds(\Oracle\M2\Connector\RegistrationInterface $registration)
    {
        $poolIds = [];
        foreach ($this->getAll($registration) as $generator) {
            if (array_key_exists('integration', $generator) && $generator['integration']) {
                $poolIds[] = $generator['id'];
            }
        }
        return $poolIds;
    }

    /**
     * Validates the generator
     *
     * @param array $generator
     * @param boolean $force
     * @return boolean
     */
    protected function _validate($generator, $force = false)
    {
        if (!$force && (!array_key_exists('enabled', $generator) || !$generator['enabled'])) {
            return false;
        }

        if (!empty($generator['endDate'])) {
            $endDate = strtotime($generator['endDate']);
            if ($endDate < time()) {
                return false;
            }
        }

        return true;
    }
}

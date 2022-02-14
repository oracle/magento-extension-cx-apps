<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Email\Model;

use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\View\DesignInterface;

class Template implements \Magento\Framework\Mail\TemplateInterface
{
    protected $_vars = [];
    protected $_options = [];
    protected $_data = [];
    protected $_encoder;
    protected $_originalId;
    protected $_helper;
    protected $_objectManager;

    /** @var \Magento\Framework\DataObjectFactory */
    protected $dataObjectFactory;

    /**
     * @var \Magento\Framework\View\DesignInterface
     */
    protected $design;

    /**
     * @var StoreManagerInterface $storeManager
     */
    protected $storeManager;

    /**
     * Configuration of design package for template
     *
     * @var DataObject
     */
    private $designConfig;

    /**
     * @param \Oracle\M2\Email\SettingsInterface $helper
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Oracle\M2\Common\Serialize\BiDirectional $encoder
     * @param string $originalId
     */
    public function __construct(
        \Oracle\M2\Email\SettingsInterface $helper,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Oracle\M2\Common\Serialize\BiDirectional $encoder,
        $originalId,
        \Magento\Framework\DataObjectFactory $dataObjectFactory,
        DesignInterface $design,
        StoreManagerInterface $storeManager
    ) {
        $this->_originalId = $originalId;
        $this->_helper = $helper;
        $this->_objectManager = $objectManager;
        $this->_encoder = $encoder;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->design = $design;
        $this->storeManager = $storeManager;
    }

    /**
     * @see parent
     */
    public function getType()
    {
        return self::TYPE_HTML;
    }

    /**
     * @see parent
     */
    public function isPlain()
    {
        return false;
    }

    /**
     * @see parent
     */
    public function setVars(array $vars)
    {
        $this->_vars = $vars;
        return $this;
    }

    /**
     * @see parent
     */
    public function setOptions(array $options)
    {
        $this->setDesignConfig($options);
        $this->_options = $options;
        if (isset($options['store'])) {
            $mappingId = $this->_helper->getLookup($this->_originalId, 'store', $options['store']);
            if ($mappingId) {
                $this->_data = $this->_helper->getMessage('mapping', $mappingId, $options['store']);
            }
        }
        if (array_key_exists('forceMagento', $this->_vars) || empty($this->_data) || !$this->_data['enabled']) {
            $original = $this->_objectManager->create(\Oracle\Email\Model\Settings::TEMPLATE, [
                'data' => [ 'template_id' => $this->_originalId ]
            ]);
            return $original
                ->setVars($this->_vars)
                ->setOptions($options);
        }
        return $this;
    }

    /**
     * Get design configuration data
     *
     * @see \Magento\Email\Model\AbstractTemplate::getDesignConfig
     *
     * @return DataObject
     */
    public function getDesignConfig()
    {
        if ($this->designConfig === null) {
            $this->designConfig = $this->dataObjectFactory->create()
                ->setArea($this->design->getArea())
                ->setStore($this->storeManager->getStore()->getId());
        }
        return $this->designConfig;
    }

    /**
     * Initialize design information for template processing
     *
     * @see \Magento\Email\Model\AbstractTemplate::setDesignConfig
     *
     * @param array $config
     * @return self
     * @throws LocalizedException
     */
    public function setDesignConfig(array $config)
    {
        if (!isset($config['area']) || !isset($config['store'])) {
            throw new LocalizedException(
                'Design config is missing either an area or store'
            );
        }
        $this->getDesignConfig()->setData($config);
        return $this;
    }

    /**
     * @see parent
     */
    public function getSubject()
    {
        return implode(':', [\Oracle\Email\Model\Settings::ORACLE_ID, $this->_options['store']]);
    }

    /**
     * @see parent
     */
    public function processTemplate()
    {
        $delivery = [];
        if ($this->_data['sendType'] == 'nosend') {
            return 'nosend';
        }
        $delivery['messageId'] = $this->_data['messageId'];
        $delivery['type'] = $this->_data['sendType'];
        $delivery['start'] = date('c');
        if (!empty($this->_data['replyTo'])) {
            $delivery['replyEmail'] = $this->_data['replyTo'];
        }
        foreach ($this->_data['sendFlags'] as $flag) {
            $delivery[$flag] = true;
        }
        $container = [];
        $delivery['fields'] = $this->_helper->createDeliveryFields(
            $this->_originalId,
            $this->_data,
            $this->_options,
            $this->_vars
        );
        $container['context'] = [];
        if ($this->_data['isSendingQueued']) {
            $extraFields = $this->_helper->getExtraFields($this->_data, $this->_vars);
            if (!empty($extraFields)) {
                $container['context'] = [
                    'event' => [
                        'delivery' => [
                            'storeId' => $this->_options['store'],
                            'area' => $this->_options['area'],
                            'message' => $this->_data + ['options' => $this->_options],
                            'context' => $extraFields
                        ]
                    ]
                ];
                /** @var \Oracle\Email\Model\Template\Filter $filter */
                $filter = $this->_helper->getTemplateFilter();
                $delivery['fields'] = array_merge($delivery['fields'], $filter->finalizeFields($extraFields));
            }
            $container['delivery'] = $delivery;
        }
        return $this->_encoder->encode($container);
    }
}

<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Impl\Core;

use Magento\Newsletter\Model\ResourceModel\Subscriber;

class Subscribers implements \Oracle\M2\Core\Subscriber\ManagerInterface
{
    protected $_subscriberFactory;
    protected $_subscriberData;
    protected $_logger;
    protected $_subscriberCache = [];

    /** @var Subscriber */
    protected $subscriberResource;

    /**
     * @param \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
     * @param \Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory $subscriberData,
     * @param Subscriber $subscriberResource
     * @param \Oracle\M2\Core\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
        \Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory $subscriberData,
        \Magento\Newsletter\Model\ResourceModel\Subscriber $subscriberResource,
        \Oracle\M2\Core\Log\LoggerInterface $logger
    ) {
        $this->_subscriberFactory = $subscriberFactory;
        $this->_subscriberData = $subscriberData;
        $this->subscriberResource = $subscriberResource;
        $this->_logger = $logger;
    }

    /**
     * @see parent
     */
    public function getById($subscriberId)
    {
        if (!array_key_exists($subscriberId, $this->_subscriberCache)) {
            $subscriber = $this->_subscriberFactory
                ->create()
                ->load($subscriberId);
            if ($subscriber->getId()) {
                $this->_subscriberCache[$subscriberId] = $subscriber;
            } else {
                return null;
            }
        }
        return $this->_subscriberCache[$subscriberId];
    }

    /**
     * @see parent
     */
    public function getByEmail($email)
    {
        if (!is_string($email)) {
            $this->_logger->critical("\$email must be of type string. " . gettype($email) . " given");
            return null;
        }

        $email = strtolower($email);
        if (!array_key_exists($email, $this->_subscriberCache)) {
            $subscriber = $this->_subscriberFactory
                ->create()
                ->loadByEmail($email);
            if ($subscriber->getId()) {
                $this->_subscriberCache[$email] = $subscriber;
            } else {
                return null;
            }
        }
        return $this->_subscriberCache[$email];
    }

    /**
     * @see parent
     */
    public function getBySubscriberEmail($email, $websiteId)
    {
        if (!is_string($email)) {
            $this->_logger->critical("\$email must be of type string. " . gettype($email) . " given");
            return null;
        }
        if(empty($websiteId)) {
            return null;
        }

        $email = strtolower($email);
        if (!array_key_exists($email, $this->_subscriberCache)) {

            $subscriber = $this->_subscriberFactory
                ->create()
                ->loadBySubscriberEmail($email, (int)$websiteId);

            if ($subscriber->getId()) {
                $this->_subscriberCache[$email] = $subscriber;
            } else {
                return null;
            }
        }
        return $this->_subscriberCache[$email];
    }

    /**
     * @see parent
     */
    public function unsubscribe($email, $ignoreStatus = false, $location = 'normal')
    {
        if ($subscriber = $this->getByEmail($email)) {
            try {
                $subscriber
                    ->setIgnoreStatus($ignoreStatus)
                    ->setLocation($location)
                    ->unsubscribe();
            } catch (\Exception $e) {
                $this->_logger->critical($e->getMessage(), $e->getTrace());
                return false;
            }
        }
        return true;
    }

    /**
     * @see parent
     */
    public function subscribe($email, $ignoreStatus = false, $location = 'normal')
    {
        $subscriber = $this->_subscriberFactory->create();
        try {
            if (!is_string($email)) {
                $this->_logger->critical("\$email must be of type string. " . gettype($email) . " given");
                return false;
            }

            $email = strtolower($email);
            $subscriber
                ->setIgnoreStatus($ignoreStatus)
                ->setLocation($location)
                ->subscribe($email);
            $this->subscriberResource->save($subscriber);
            $this->_subscriberCache[$email] = $subscriber;
            return true;
        } catch (\Exception $e) {
            $this->_logger->critical($e->getMessage(), $e->getTrace());
            return false;
        }
    }

    /**
     * @see parent
     * @return \Magento\Newsletter\Model\ResourceModel\Subscriber\Collection
     */
    public function getCollection()
    {
        return $this->_subscriberData->create();
    }
}

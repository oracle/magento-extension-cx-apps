<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Email\Trigger;

class Observer
{
    protected $_triggerManager;
    protected $_helper;
    protected $_connectorSettings;
    protected $_productRepo;
    protected $_settings;
    protected $_siteId;
    protected $_customerRepo;

    /** @var \Oracle\M2\Core\Log\LoggerInterface */
    protected $logger;

    /**
     * @param \Oracle\M2\Email\TriggerManagerInterface $triggerManager
     * @param \Oracle\M2\Email\SettingsInterface $helper
     * @param \Oracle\M2\Connector\SettingsInterface $connectorSettings
     * @param \Oracle\M2\Core\Catalog\ProductCacheInterface $productRepo
     * @param \Oracle\M2\Core\Customer\CacheInterface $customerRepo
     * @param \Oracle\M2\Integration\CartSettingsInterface $settings
     * @param \Oracle\M2\Core\Log\LoggerInterface
     */
    public function __construct(
        \Oracle\M2\Email\TriggerManagerInterface $triggerManager,
        \Oracle\M2\Email\SettingsInterface $helper,
        \Oracle\M2\Connector\SettingsInterface $connectorSettings,
        \Oracle\M2\Core\Catalog\ProductCacheInterface $productRepo,
        \Oracle\M2\Core\Customer\CacheInterface $customerRepo,
        \Oracle\M2\Integration\CartSettingsInterface $settings,
        \Oracle\M2\Core\Log\LoggerInterface $logger
    ) {
        $this->_triggerManager = $triggerManager;
        $this->_helper = $helper;
        $this->_connectorSettings = $connectorSettings;
        $this->_productRepo = $productRepo;
        $this->_settings = $settings;
        $this->_customerRepo = $customerRepo;
        $this->logger = $logger;
    }

    /**
     * Whenever a cart updates, update Reminder triggers
     *
     * @param mixed $observer
     * @return void
     */
    public function updateQuoteTrigger($observer)
    {
        $quote = $observer->getQuote();
        if (0 == $quote->getItemsCount()) {
            $this->removeQuoteTrigger($observer);
        } else {
            $this->_handleReminder('cart', $quote);
        }
    }

    /**
     * Whever a wishlist updates, update Reminder triggers
     *
     * @param mixed $observer
     * @return void
     */
    public function updateWishlistTrigger($observer)
    {
        $wishlist = $observer->getObject();
        if (0 === $wishlist->getItemCollection()->getSize()) {
            $this->removeWishlistTrigger($observer);
        } else {
            $this->_handleReminder('wishlist', $wishlist);
        }
    }

    /**
     * Whenever an order is updated, scan applicable triggers
     *
     * @param mixed $observer
     * @return void
     */
    public function updateOrderTrigger($observer)
    {
        $order = $observer->getOrder();
        // When an order is saved, NOT coming from a placement, we must skip
        if ($order->getStatus() == 'pending' && is_null($order->getOrigData('status')) && !$observer->hasQuote()) {
            return;
        }
        list($orderType, $orderId) = $this->_helper->getModelTuple($order);
        if ($order->dataHasChangedFor('status')) {
            $messageTypes = [];
            foreach (['reorder', 'caretip'] as $type) {
                if ($this->_helper->isMessageEnabled($type, 'store', $order->getStoreId())) {
                    $messageTypes[$type] = $this->_helper->getActiveObjects($type, $order->getStore());
                }
            }
            $review = $this->_helper->getMessage('review', 'review', $order->getStore());
            $existingReviews = $this->_triggerManager->getTriggers($this->getSiteId($order->getStoreId()), $orderType, $orderId);
            if (!empty($review)) {
                $messageTypes['review'] = ['review' => $review];
            }
            if (!empty($messageTypes)) {
                $groupId = (int) $order->getCustomerGroupId();
                $reviewTriggered = false;
                foreach ($order->getAllVisibleItems() as $lineItem) {
                    list($modelType, $modelId) = $this->_helper->getModelTuple($lineItem);
                    $existingTriggers = $existingReviews + $this->_triggerManager->getTriggers($this->getSiteId($order->getStoreId()), $modelType, $modelId);
                    $product = $this->_productRepo->getById($lineItem->getProductId(), $order->getStoreId());
                    foreach ($messageTypes as $messageType => $objects) {
                        if ($reviewTriggered && $messageType == 'review') {
                            continue;
                        }
                        foreach ($objects as $objectId => $object) {
                            foreach ($product->getCategoryIds() as $catgoryId) {
                                if (!in_array($groupId, $object['targetAudience'])) {
                                    continue 2;
                                }
                                if (isset($object['exclusionCategories']) && in_array($catgoryId, $object['exclusionCategories'])) {
                                    continue 2;
                                }
                                if (empty($object['categories']) || in_array($catgoryId, $object['categories'])) {
                                    $this->_handlePostPurchase(
                                        $order,
                                        $messageType,
                                        $existingTriggers,
                                        $objectId,
                                        $object,
                                        $objectId == 'review' ? $orderType : $modelType,
                                        $objectId == 'review' ? $orderId : $modelId,
                                        $lineItem->getQtyOrdered()
                                    );
                                    if ($objectId == 'review') {
                                        $reviewTriggered = true;
                                    }
                                    continue 2;
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Deletes all of the triggers associated with a wishlist
     *
     * @param mixed $observer
     * @return void
     */
    public function removeWishlistTrigger($observer)
    {
        $this->_deleteModelTrigger($observer->getObject());
    }

    /**
     * Deletes all of the triggers associated with a quote
     *
     * @param mixed $observer
     * @return void
     */
    public function removeQuoteTrigger($observer)
    {
        $this->_deleteModelTrigger($observer->getQuote());
    }

    /**
     * Deletes all of the triggers associated with an order
     *
     * @param mixed $observer
     * @return void
     */
    public function removeOrderTrigger($observer)
    {
        $order = $this->_deleteModelTrigger($observer->getOrder());
        foreach ($order->getAllVisibleItems() as $lineItem) {
            $this->_deleteModelTrigger($lineItem);
        }
    }

    /**
     * Stores a reminder trigger for a cart or a wishlist
     *
     * @param string $modelType
     * @param mixed $model
     * @return void
     */
    protected function _handleReminder($modelType, $model)
    {
        $store = null;
        if ($modelType == 'cart') {
            $emailAddress = $model->getCustomerEmail();
            $store = $model->getStore();
            if (empty($emailAddress)) {
                $emailAddress = $this->_settings->getCartRecoveryEmail($model);
                if (empty($emailAddress)) {
                    $this->logger->debug("Could not handle reminder. No email address associated with cart ID {$model->getId()}.");
                    return;
                }
            }
        } else {
            $customer = $this->_customerRepo->getById($model->getCustomerId());
            if (empty($customer)) {
                return;
            }
            $store = $customer->getStore();
            $emailAddress = $customer->getEmail();
        }
        $storeId = $store->getId();
        $existingTriggers = $this->_triggerManager->getTriggers($this->getSiteId($storeId), $modelType, $model->getId());
        $reminders = $this->_helper->getActiveObjects($modelType, $store);
        $updatedAt = strtotime($model->getUpdatedAt());
        if ($updatedAt === false || $updatedAt < 0) {
            $updatedAt = strtotime('now');
        }
        foreach ($reminders as $reminderId => $reminder) {
            // Will want to exclude carts without emails
            if ($modelType == 'cart' && !in_array($model->getCustomerGroupId(), $reminder['targetAudience'])) {
                continue;
            }
            $trigger = $this->_triggerManager->createTrigger($this->getSiteId($storeId), $modelType, $reminderId);
            if (array_key_exists($reminderId, $existingTriggers)) {
                $trigger = $existingTriggers[$reminderId];
                if ($trigger->getSentMessage()) {
                    continue;
                }
            }
            $newTime = strtotime("+{$reminder['abandonPeriod']} {$reminder['abandonUnit']}", $updatedAt);
            if (isset($reminder['previousMessage'])) {
                if (!array_key_exists($reminder['previousMessage'], $existingTriggers)) {
                    continue;
                }
                $previous = $reminders[$reminder['previousMessage']];
                $newTime = strtotime("+{$previous['abandonPeriod']} {$previous['abandonUnit']}", $newTime);
            }
            $this->_triggerManager->save($trigger
                ->setCustomerEmail($emailAddress)
                ->setTriggeredAt($newTime)
                ->setModel($modelType, $model->getId(), $storeId));
            $existingTriggers[$reminderId] = $trigger;
        }
    }

    /**
     * Lots of entries for post purchase handles
     *
     * @param mixed $order
     * @param string $messageType
     * @param array $existingTriggers
     * @param string $objectId
     * @param array $object
     * @param string $modelType
     * @param mixed $modelId
     * @param int $qtyOrdered
     * @return void
     */
    protected function _handlePostPurchase($order, $messageType, $existingTriggers, $objectId, $object, $modelType, $modelId, $qtyOrdered)
    {
        $updatedAt = strtotime($order->getUpdatedAt() ?
            $order->getUpdatedAt() :
            $order->getCreatedAt());
        $handle = $this->_handlePostPurchaseState($order, $object);
        $trigger = $this->_triggerManager->createTrigger($this->getSiteId($order->getStoreId()), $messageType, $objectId);
        if (array_key_exists($objectId, $existingTriggers)) {
            $trigger = $existingTriggers[$objectId];
        }
        if ($handle === false) {
            $this->_triggerManager->delete($trigger);
        } elseif ($handle === true && !$trigger->getSentMessage()) {
            $sendPeriod = $object['sendPeriod'];
            if (isset($object['multiply']) && $object['multiply'] === true) {
                $sendPeriod *= (int)$qtyOrdered;
            }
            $newTime = strtotime("+{$sendPeriod} days", $updatedAt);
            $this->_triggerManager->save($trigger
                ->setCustomerEmail($order->getCustomerEmail())
                ->setTriggeredAt($newTime)
                ->setModel($modelType, $modelId, $order->getStoreId()));
        }
    }

    /**
     * Deletes all of the triggers stored for deleted objects
     *
     * @param mixed $model
     * @return $model
     */
    protected function _deleteModelTrigger($model)
    {
        list($modelType, $modelId) = $this->_helper->getModelTuple($model);
        foreach ($this->_triggerManager->getTriggers($this->getSiteId($model->getStore()), $modelType, $modelId) as $trigger) {
            $this->_triggerManager->delete($trigger);
        }
        return $model;
    }

    /**
     * Determines the status movement by triggerable status and
     * changed data
     *
     * @param mixed $order
     * @param array $object
     * @return mixed
     */
    protected function _handlePostPurchaseState($order, $object)
    {
        $inAddStatus = $order->getStatus() == $object['triggerStatus'];
        $inCancelStatus = in_array($order->getStatus(), $object['cancelStatus']);
        if (!$inAddStatus && !$inCancelStatus) {
            return null;
        } elseif ($inCancelStatus) {
            return false;
        } else {
            return $inAddStatus;
        }
    }

    /**
     * Gets the site hash for the store view
     *
     * @param mixed $store
     * @return string
     */
    protected function getSiteId($store)
    {
        if (is_null($this->_siteId)) {
            $this->_siteId = $this->_connectorSettings->getSiteId('store', $store);
        }
        return $this->_siteId;
    }
}

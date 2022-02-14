<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Email\Model;

class Trigger extends \Magento\Framework\Model\AbstractModel implements \Oracle\M2\Email\TriggerInterface
{
    /**
     * @see parent
     */
    protected function _construct()
    {
        $this->_init('Oracle\Email\Model\ResourceModel\Trigger');
    }

    /**
     * @see parent
     */
    public function getTriggeredAt()
    {
        return $this->getData(self::FIELD_TRIGGERED_AT);
    }

    /**
     * @see parent
     */
    public function getModelType()
    {
        return $this->getData(self::FIELD_MODEL_TYPE);
    }

    /**
     * @see parent
     */
    public function getModelId()
    {
        return $this->getData(self::FIELD_MODEL_ID);
    }

    /**
     * @see parent
     */
    public function getMessageId()
    {
        return $this->getData(self::FIELD_MESSAGE_ID);
    }

    /**
     * @see parent
     */
    public function getMessageType()
    {
        return $this->getData(self::FIELD_MESSAGE_TYPE);
    }

    /**
     * @see parent
     */
    public function getSentMessage()
    {
        return $this->getData(self::FIELD_SENT_MESSAGE);
    }

    /**
     * @see parent
     */
    public function setSentMessage($value)
    {
        $this->setData(self::FIELD_SENT_MESSAGE, $value);
        return $this;
    }

    /**
     * @see parent
     */
    public function getCustomerEmail()
    {
        return $this->getData(self::FIELD_CUSTOMER_EMAIL);
    }

    /**
     * @see parent
     */
    public function getSiteId()
    {
        return $this->getData(self::FIELD_SITE_ID);
    }

    /**
     * @see parent
     */
    public function getStoreId()
    {
        return $this->getData(self::FIELD_STORE_ID);
    }

    /**
     * @see parent
     */
    public function setCustomerEmail($email)
    {
        $this->setData(self::FIELD_CUSTOMER_EMAIL, $email);
        return $this;
    }

    /**
     * @see parent
     */
    public function setTriggeredAt($newTime)
    {
        $this->setData(self::FIELD_TRIGGERED_AT, $newTime);
        return $this;
    }

    /**
     * @see parent
     */
    public function setModel($modelType, $modelId, $storeId)
    {
        $this->setData(self::FIELD_MODEL_TYPE, $modelType);
        $this->setData(self::FIELD_MODEL_ID, $modelId);
        $this->setData(self::FIELD_STORE_ID, $storeId);
        return $this;
    }
}

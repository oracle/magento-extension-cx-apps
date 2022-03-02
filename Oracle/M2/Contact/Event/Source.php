<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Contact\Event;

class Source implements \Oracle\M2\Connector\Event\SourceInterface, \Oracle\M2\Connector\Event\ContextProviderInterface
{
    protected $_helper;

    /**
     * @param \Oracle\M2\Contact\SettingsInterface $helper
     */
    public function __construct(
        \Oracle\M2\Contact\SettingsInterface $helper
    ) {
        $this->_helper = $helper;
    }

    /**
     * @see parent
     */
    public function create($customer)
    {
        return [
          'updated_email' => $customer->dataHasChangedFor('email') ?
              $customer->getOrigData('email') :
              $customer->getEmail(),
          'uniqueKey' => implode('.', [
              $this->getEventType(),
              $this->action($customer),
              $customer->getId()
          ]),
          'resetPassword' => $customer->hasData("resetPassword"),
          'forgotPassword' => $customer->hasData("forgotPassword")
        ];
    }

    /**
     * @see parent
     */
    public function getEventType()
    {
        return 'contact';
    }

    /**
     * @see parent
     */
    public function action($customer)
    {
        return $customer->getIsUpdateEmail() ? 'update' : 'add';
    }

    /**
     * @see parent
     */
    public function transform($customer)
    {
        if ($customer->getIsUpdateEmail() && $customer->dataHasChangedFor('email')) {
            return [
                'id' => $customer->getOrigData('email'),
                'email' => $customer->getEmail()
            ];
        } else {
            return [
                'email' => $customer->getEmail(),
                'status' => 'transactional',
                'fields' => $this->_helper->getFieldsForModel($customer, $customer->getStore())
            ];
        }
    }

    public function transformForEvent($customer, $eventName) {
        return [
            'email' => $customer->getEmail(),
            'status' => 'transactional',
            'fields' => $this->_helper->getFieldsForModel($customer, $customer->getStore(), 'contact', $eventName)
        ];
    }

}

<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Cart\Event;

class Source extends \Oracle\M2\Order\Event\CartBasedSourceAbstract
{
    protected $_settings;

    /**
     * @param \Oracle\M2\Cart\SettingsInterface $settings
     * @param \Oracle\M2\Connector\SettingsInterface $connector
     * @param \Oracle\M2\Order\SettingsInterface $helper
     * @param \Oracle\M2\Core\Cookie\ReaderInterface $cookie
     */
    public function __construct(
        \Oracle\M2\Cart\SettingsInterface $settings,
        \Oracle\M2\Connector\SettingsInterface $connector,
        \Oracle\M2\Order\SettingsInterface $helper,
        \Oracle\M2\Core\Cookie\ReaderInterface $cookie
    ) {
        parent::__construct($connector, $helper, $settings, $cookie);
        $this->_settings = $settings;
    }

    /**
     * @see parent
     */
    public function create($quote)
    {
        $action = $this->action($quote);
        return [
            'is_new' => $quote->isObjectNew(),
            'is_deleted' => $quote->isDeleted(),
            'redirect_url' => $this->_settings->getRedirectUrl($quote->getId(), $quote->getStore()),
            'emailAddress' => $this->_settings->getCartRecoveryEmail($quote),
            'uniqueKey' => implode('.', [
                $this->getEventType(),
                $action[0],
                $this->_status($quote),
                $quote->getId()
            ])
        ];
    }

    /**
     * @see parent
     */
    public function action($quote)
    {
        if (!$quote->getId()) {
            return '';
        } else {
            return $quote->isDeleted() || $quote->getItemsCount() == 0 ?
                'delete' :
                'replace';
        }
    }

    /**
     * @see parent
     */
    public function getEventType()
    {
        return 'cart';
    }

    /**
     * Override for delete short circuits
     *
     * @see parent
     */
    public function transform($quote)
    {
        if ($quote->isDeleted()) {
            return [
                'customerCartId' => $quote->getId(),
                'status' => $this->_status($quote)
            ];
        } else {
            return parent::transform($quote);
        }
    }

    /**
     * @see parent
     */
    protected function _initializeData($quote, $isBase)
    {
        $data = [
            'emailAddress' => $this->_settings->getCartRecoveryEmail($quote),
            'customerCartId' => $quote->getId(),
            'url' => $quote->hasRedirectUrl() ?
                $quote->getRedirectUrl() :
                $this->_settings->getRedirectUrl($quote->getId(), $quote->getStore()),
            'status' => $this->_status($quote),
            'phase' => $this->_phase($quote),
            'currency' => $isBase ?
                $quote->getBaseCurrencyCode() :
                $quote->getQuoteCurrencyCode(),
        ];
        return $data;
    }

    /**
     * Returns the desired cart phase based on quote info
     *
     * @param mixed $quote
     * @return string
     */
    protected function _status($quote)
    {
        if ($quote->isDeleted()) {
            return 'EXPIRED';
        }
        if ($quote->getReservedOrderId()) {
            return 'COMPLETE';
        } elseif ($quote->isObjectNew() || $quote->getIsActive()) {
            return 'ACTIVE';
        } else {
            return 'EXPIRED';
        }
    }

    /**
     * Returns the appropriate cart phase based on the quote info
     *
     * @param mixed $quote
     * @return string
     */
    protected function _phase($quote)
    {
        $phase = 'SHOPPING';
        if ($quote->getReservedOrderId()) {
            $phase = 'ORDER_COMPLETE';
        }
        return $phase;
    }
}

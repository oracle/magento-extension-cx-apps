<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Browse\Controller\Browse;

class Capture extends \Magento\Framework\App\Action\Action
{
    const BROWSE_EVENT_NAME = 'oracle_browse_event';

    protected $_productRepo;
    protected $_storeManager;
    protected $_eventManager;
    protected $_queryFactory;

    /**
     * @param \Oracle\M2\Core\Store\ManagerInterface $storeManager
     * @param \Oracle\M2\Core\Event\ManagerInterface $eventManager
     * @param \Oracle\M2\Core\Catalog\ProductCacheInterface $productRepo
     * @param \Magento\Search\Model\QueryFactory $queryFactory
     * @param \Magento\Framework\App\Action\Context $context
     */
    public function __construct(
        \Oracle\M2\Core\Store\ManagerInterface $storeManager,
        \Oracle\M2\Core\Event\ManagerInterface $eventManager,
        \Oracle\M2\Core\Catalog\ProductCacheInterface $productRepo,
        \Magento\Search\Model\QueryFactory $queryFactory,
        \Magento\Framework\App\Action\Context $context
    ) {
        parent::__construct($context);
        $this->_productRepo = $productRepo;
        $this->_storeManager = $storeManager;
        $this->_eventManager = $eventManager;
        $this->_queryFactory = $queryFactory;
    }

    /**
     * @see parent
     */
    public function execute()
    {
        // event_type can be view or search.
        $eventType = $this->getRequest()->getParam('event_type', 'VIEW');
        $currentStore = $this->_storeManager->getStore(true);
        $transform = '_' . strtolower($eventType) . 'Event';
        if (method_exists($this, $transform)) {
            $this->{$transform}($currentStore);
        }
    }

    /**
     * Generates a single product view event
     *
     * @param mixed $store
     * @return mixed
     */
    protected function _viewEvent($store)
    {
        $productId = (int) $this->getRequest()->getParam('id');
        $categoryId = (int) $this->getRequest()->getParam('category_id', null);
        $product = $this->_productRepo->getById($productId, $store->getId());
        $this->_eventManager->dispatch(self::BROWSE_EVENT_NAME, [
            'request' => $this->getRequest(),
            'product' => $product
        ]);
    }

    /**
     * Generates an array of product search events
     *
     * @param mixed $store
     * @return mixed
     */
    protected function _searchEvent($store)
    {
        $layer = $this->_objectManager->get('Magento\Catalog\Model\Layer\Search');
        $query = $this->_queryFactory->get();
        $collection = $layer->getProductCollection();
        foreach ($collection as $product) {
            $this->_eventManager->dispatch(self::BROWSE_EVENT_NAME, [
                'request' => $this->getRequest(),
                'product' => $product,
                'event_type' => 'SEARCH',
                'event_type_value' => $query->getQueryText()
            ]);
        }
    }
}

<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Impl\Core;

class CategoryResolverFactory implements \Oracle\M2\Core\Catalog\CategoryResolverFactoryInterface
{
    const RESOLVER = 'Oracle\M2\Core\Catalog\CategoryResolverInterface';

    protected $_objectManager;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->_objectManager = $objectManager;
    }

    /**
     * @see parent
     */
    public function create($resolver, $product)
    {
        return $this->_objectManager->create(self::RESOLVER);
    }
}

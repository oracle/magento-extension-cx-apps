<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Impl\Core;

class GroupCacheBridge implements \Oracle\M2\Core\Customer\GroupCacheInterface
{
    protected $_groupRepo;

    /**
     * @param \Magento\Customer\Api\GroupRepositoryInterface $groupRepo
     */
    public function __construct(
        \Magento\Customer\Api\GroupRepositoryInterface $groupRepo
    ) {
        $this->_groupRepo = $groupRepo;
    }

    /**
     * @see parent
     */
    public function getById($groupId)
    {
        try {
            return $this->_groupRepo->getById($groupId);
        } catch (\Exception $e) {
            return null;
        }
    }
}

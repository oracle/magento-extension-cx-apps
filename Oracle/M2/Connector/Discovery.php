<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Connector;

use Oracle\M2\Connector\Discovery\GroupInterface;

class Discovery
{
    private $_increment = 1;
    private $_groups = [];

    /**
     * Add a group object to the discovery document
     *
     * @param array $group
     * @return $this
     */
    public function addGroup(array $group)
    {
        if (!array_key_exists('sort_order', $group)) {
            $wrapped = [
                'definition' => $group,
                'sort_order' => $this->_increment++
            ];
            $group = $wrapped;
        }
        $this->_groups[] = $group;
        return $this;
    }

    /**
     * Add a an extension group from a GroupInterface
     *
     * @param \Oracle\Connector\Model\Discovery\GroupInterface $group
     * @return $this
     */
    public function addGroupHelper(GroupInterface $group)
    {
        return $this->addGroup([
            'sort_order' => $group->getSortOrder(),
            'definition' => [
                'id' => $group->getEndpointId(),
                'name' => $group->getEndpointName(),
                'icon' => $group->getEndpointIcon()
            ]
        ]);
    }

    /**
     * Gets the extension group objects for the discovery document
     *
     * @return array
     */
    public function getGroups()
    {
        return $this->_groups;
    }
}

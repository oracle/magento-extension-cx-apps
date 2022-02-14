<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Core\Catalog;

class CategoryResolverGeneric implements CategoryResolverInterface
{
    protected $_delimiter;
    protected $_format;
    protected $_selection;
    protected $_specificity;
    protected $_broadness;
    protected $_encapsulate;

    /**
     * @param string $encapsulate
     * @param string $delimiter
     * @param string $format
     * @param string $selection
     * @param mixed $specificity
     * @param mixed $broadness
     */
    public function __construct(
        $encapsulate = '|',
        $delimiter = ' ',
        $format = 'urlKey',
        $selection = 'all',
        $specificity = null,
        $broadness = null
    ) {
        $this->_delimiter = $delimiter;
        $this->_format = $format;
        $this->_selection = $selection;
        $this->_specificity = $specificity;
        $this->_broadness = $broadness;
        $this->_encapsulate = $encapsulate;
    }

    /**
     * @see parent
     */
    public function resolve($branches)
    {
        if ($this->_selection == 'all') {
            $output = [];
            foreach ($branches as $branch) {
                $output[] = $this->branch($branch);
            }
            return implode('', array_map([$this, 'encapsulate'], $output));
        } else {
            $categories = [];
            foreach ($branches as $branch) {
                $categories = array_merge($categories, $this->specify($branch));
            }
            if ($this->_selection == 'single') {
                $categories = $this->specify($categories);
            }
            return $this->branch($categories);
        }
    }

    /**
     * Surrounds output with encapsulation
     *
     * @param string $output
     * @return string
     */
    public function encapsulate($output)
    {
        return $this->_encapsulate . $output . $this->_encapsulate;
    }

    /**
     * Takes in a branch of categories and formats appropriately
     *
     * @param array $branch
     * @return string
     */
    public function branch($branch)
    {
        return implode($this->_delimiter, array_map([$this, 'format'], $branch));
    }

    /**
     * Turns a category into a string
     *
     * @param mixed $category
     * @return string
     */
    public function format($category)
    {
        if ($this->_format == 'urlKey') {
            return $category->getUrlKey() ?
                $category->getUrlKey() :
                $category->formatUrlKey($category->getName());
        } else {
            return $category->getName();
        }
    }

    /**
     * Filters a category by its sort rule
     *
     * @param array $categories
     * @return array
     */
    public function specify($categories)
    {
        if ($this->_selection == 'all' || empty($categories)) {
            return $categories;
        } else {
            usort($categories, [$this, '_sortCategory']);
            return [$categories[0]];
        }
    }

    /**
     * Applies the specific / broadness sort
     *
     * @param mixed $categoryA
     * @param mixed $categoryB
     * @return int
     */
    protected function _sortCategory($categoryA, $categoryB)
    {
        $specificityA = $categoryA->getLevel();
        $specificityB = $categoryB->getLevel();
        if ($specificityA == $specificityB) {
            $broadnessA = $categoryA->getChildrenCount();
            $broadnessB = $categoryB->getChildrenCount();
            if ($broadnessA == $broadnessB) {
                $positionA = $categoryA->getPosition();
                $positionB = $categoryB->getPosition();
                if ($positionA == $positionB) {
                    return 0;
                }
                return $positionA > $positionB ? -1 : 1;
            } elseif ($broadnessA > $broadnessB) {
                return $this->_broadness == 'most' ? -1 : 1;
            } else {
                return $this->_broadness == 'most' ? 1 : -1;
            }
        } elseif ($specificityA < 2 || $specificityB < 2) {
            return $specificityA < $specificityB ? 1 : -1;
        } elseif ($specificityA > $specificityB) {
            return $this->_specificity == 'highest-1' ? 1 : -1;
        } else {
            return $this->_specificity == 'highest-1' ? -1 : 1;
        }
    }
}

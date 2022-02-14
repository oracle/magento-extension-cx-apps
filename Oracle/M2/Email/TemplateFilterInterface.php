<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Email;

interface TemplateFilterInterface
{
    /**
     * Sets any variables on the template
     *
     * @param array $templateVars
     * @return $this
     */
    public function setVariables($templateVars = []);

    /**
     * Adds a filter event listener to the template filter
     *
     * @param FilterEventInterface $filter
     * @return $this
     */
    public function addEventFilter(FilterEventInterface $filter);

    /**
     * Gets a key => value pair context derived
     *
     * @param array $message
     * @param boolean $forceContext
     * @return array
     */
    public function getContext($message, $forceContext = true);

    /**
     * Gets a list of replaced tags in the template
     *
     * @return array
     */
    public function getReplacedTags();

    /**
     * Transforms the context into Oracle API tags
     *
     * @param array $message
     * @return array
     */
    public function applyAndTransform($message);
}

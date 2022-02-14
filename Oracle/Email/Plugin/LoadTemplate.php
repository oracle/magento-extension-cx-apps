<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Email\Plugin;

class LoadTemplate
{
    protected $_helper;
    protected $_templateFactory;

    /**
     * @param \Oracle\M2\Email\SettingsInterface $helper
     * @param \Oracle\Email\Model\TemplateFactory $templateFactory
     */
    public function __construct(
        \Oracle\M2\Email\SettingsInterface $helper,
        \Oracle\Email\Model\TemplateFactory $templateFactory
    ) {
        $this->_helper = $helper;
        $this->_templateFactory = $templateFactory;
    }

    /**
     * Creates a Oracle Template if provided, or fallback to existing
     *
     * @param mixed $subject
     * @param callable $get
     * @param mixed $templateId
     */
    public function aroundGet($subject, $get, $templateId, $namespace = null)
    {
        // Replaces initial workflow with injected workflow.
        // It's not yet possible to know which context should
        // be loaded, so that logic is passed to the Template
        $lookup = $this->_helper->getLookup($templateId);
        if (!empty($lookup)) {
            return $this->_templateFactory->create([ 'originalId' => $templateId ]);
        }
        return $get($templateId, $namespace);
    }
}

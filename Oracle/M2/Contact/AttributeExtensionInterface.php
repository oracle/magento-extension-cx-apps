<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Contact;

interface AttributeExtensionInterface extends \Oracle\M2\Connector\Discovery\TranslationInterface
{
    /**
     * Adds additional mappings to the customer attribute
     * section to send reward balance
     *
     * @param mixed $observer
     * @return void
     */
    public function contactAdditional($observer);

    /**
     * Loads the additional fields from the reward model
     *
     * @param mixed $observer
     * @return void
     */
    public function contactLoadFields($observer);
}

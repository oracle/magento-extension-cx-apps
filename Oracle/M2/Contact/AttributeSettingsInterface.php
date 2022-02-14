<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Contact;

interface AttributeSettingsInterface
{
    /**
     * Returns a hash map of the extra field settings
     *
     * @return array
     */
    public function getFields();

    /**
     * Returns a hash map of the extra field values
     *
     * @param mixed $contact
     * @param mixed $storeId
     * @return array
     */
    public function getExtra($contact, $storeId = null);
}

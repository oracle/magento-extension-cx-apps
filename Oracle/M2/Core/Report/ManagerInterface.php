<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Core\Report;

interface ManagerInterface
{
    /**
     * Determines if the report key in question corresponds to a report collection
     *
     * @param string $reportKey
     * @return boolean
     */
    public function isReportKey($reportKey);

    /**
     * Gets the time at which the report was aggregated
     *
     * @param string $reportKey
     * @return string
     */
    public function getLastUpdate($reportKey);

    /**
     * Refreshes the report collection within the specified range
     *
     * @param string $reportKey
     * @param mixed $fromTime
     * @param mixed $toTime
     * @return boolean
     */
    public function refresh($reportKey, $fromTime = null, $toTime = null);
}

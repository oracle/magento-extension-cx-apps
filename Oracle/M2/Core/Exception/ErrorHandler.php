<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Core\Exception;

/**
 * Class ErrorHandler
 *
 * @package Oracle\M2\Core\Exception
 */
class ErrorHandler
{

    /**
     * Can be registered to throw \ErrorException when a registered error occurs
     *
     * @param int $severity
     * @param string $message
     * @param string $file
     * @param int $line
     * @throws \ErrorException
     */
    public static function handleError($severity, $message, $file, $line)
    {
        if (!(error_reporting() & $severity)) {
            return;
        }
        throw new \ErrorException($message, 0, $severity, $file, $line);
    }
}

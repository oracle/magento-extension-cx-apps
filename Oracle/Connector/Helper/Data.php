<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Connector\Helper;

use Psr\Log\LoggerInterface;
use Magento\Framework\App\Helper\AbstractHelper;

class Data extends AbstractHelper
{
    /**
     * Keep fixed properties from being overwritten based upon the mapping given
     *
     * @param array $fixedPropertiesMapping
     * @param array $oldEventDatum
     * @param array $newEventDatum
     * @return mixed
     */
    public static function preserveFixedProperties(array $fixedPropertiesMapping, array $oldEventDatum, array $newEventDatum)
    {
        $commonProperties = array_intersect_key($oldEventDatum, $fixedPropertiesMapping);
        if (!empty($commonProperties)) {
            foreach ($commonProperties as $key => $value) {
                if (!is_array($fixedPropertiesMapping[$key])) {
                    $newEventDatum[$key] = $value;
                    continue;
                }
                $newEventDatum[$key] = static::preserveFixedProperties(
                    $fixedPropertiesMapping[$key],
                    $oldEventDatum[$key],
                    isset($newEventDatum[$key]) && is_array($newEventDatum[$key]) ? $newEventDatum[$key] : []
                );
            }
        }

        return $newEventDatum;
    }

    /**
     * Captures fatal errors and logs the origin in the exception log.
     * This is designed to be registered as a shutdown function.
     */
    public static function fatalErrorHandler(LoggerInterface $logger) {
        $error = error_get_last();
        if ($error !== null && $error["type"] === E_ERROR) {
            $logger->critical(sprintf(
                "Fatal error occurred while handling a Oracle request -- %s(%s): %s",
                $error["file"],
                $error["line"],
                $error["message"]
            ));
        }
    }
}

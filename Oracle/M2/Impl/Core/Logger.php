<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Impl\Core;

class Logger implements \Oracle\M2\Core\Log\LoggerInterface
{
    private $_logger;

    /**
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->_logger = $logger;
    }

    /**
     * @see parent
     */
    public function debug($message, array $context = [])
    {
        $this->_logger->debug($message, $context);
    }

    /**
     * @see parent
     */
    public function info($message, array $context = [])
    {
        $this->_logger->info($message, $context);
    }

    /**
     * @see parent
     */
    public function critical($message, array $context = [])
    {
        $this->_logger->critical($message, $context);
    }

    /**
     * Returns a backtrace with just file a line number for compact logging
     *
     * For debug logging only.
     * 
     * @return string
     */
    public function getSimplifiedBackTrace()
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $simplifiedBacktrace = [];
        foreach ($backtrace as $index => $element) {
            if (isset($element['file'])) {
                $simplifiedElement = "#{$index} {$element['file']}";
                $simplifiedElement .= isset($element['line']) ? ": {$element['line']}" : '';
                $simplifiedBacktrace[] = $simplifiedElement;
            }
        }

        return (!empty($simplifiedBacktrace)) ? implode("\n\t", $simplifiedBacktrace) : '';
    }
}

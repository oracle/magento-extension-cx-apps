<?php

namespace Oracle\M2\Common;

/**
 * A simple utility class containing simple string
 * manipulations.
 *
 * @author Philip Cali <philip.cali@oracle.com>
 */
class Utils
{
    /**
     * Camel case to underscore conversion
     *
     * @param string $name
     * @return string
     */
    public static function underscore($name)
    {
        $pattern = '/([a-z0-9])([A-Z])/';
        $underscores = preg_replace($pattern, "$1_$2", $name);
        return strtolower($underscores);
    }

    /**
     * Normalize the name to use no punctuation and underscores
     *
     * @param string $name
     * @return string
     */
    public static function normalize($name)
    {
        $name = preg_replace("/[^a-z0-9_]/i", '_', strtolower($name));
        $name = trim(preg_replace('/_+/', '_', $name), ' _');
        return $name;
    }

    /**
     * Simple pluralization for common names
     *
     * @param string $name
     * @return string
     */
    public static function pluralize($name)
    {
       if (preg_match('/y$/', $name)) {
            $name = preg_replace('/y$/', 'ies', $name);
        } else {
            $name .= 's';
        }
        return $name;
    }
    
    /**
     * Returns a string representation of the supplied argument.
     * 
     * @param mixed $var
     * @return string
     */
    public static function stringify($var)
    {
        if (is_object($var)) {
            $var = method_exists($var, '__toString') ? strval($var) : print_r($var, true);
        } elseif (is_array($var)) {
            $var = print_r($var, true);
        } elseif (is_bool($var)) {
            $var = $var ? 'true (boolean)' : 'false (boolean)';
        }

        return strval($var);
    }
}

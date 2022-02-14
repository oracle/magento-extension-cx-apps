<?php

namespace Oracle\M2\Common\Functional;

/**
 * The None type represents a "Nothing" container
 */
class None extends Option
{
    /**
     * @return boolean
     */
    public function isDefined()
    {
        return false;
    }

    /**
     * @throws \BadMethodCallException
     */
    public function get()
    {
        throw new \BadMethodCallException('None->get() cannot not be invoked.');
    }
}

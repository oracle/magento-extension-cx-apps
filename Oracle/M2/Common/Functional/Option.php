<?php

namespace Oracle\M2\Common\Functional;

/**
 * Container type that implements Monadic operations
 *
 * @author Philip Cali <philip.cali@oracle.com>
 */
abstract class Option implements Monadic
{
    /**
     * Whether or not the container contains a value
     *
     * @return boolean
     */
    public abstract function isDefined();

    /**
     * Retrieves the contained value
     *
     * @return mixed
     */
    public abstract function get();

    /**
     * Whether or not the container is empty
     *
     * @return boolean
     */
    public function isEmpty()
    {
        return !$this->isDefined();
    }

    /**
     * @see parent
     * @param callable $function
     * @return Monadic
     */
    public function each($function)
    {
        if ($this->isDefined()) {
            call_user_func($function, $this->get());
        }
        return $this;
    }

    /**
     * @see parent
     * @param callable $function
     * @return Monadic
     */
    public function filter($function)
    {
        if ($this->isDefined() && call_user_func($function, $this->get())) {
            return $this;
        }
        return new None();
    }

    /**
     * @see parent
     * @param callable $function
     * @return Monadic
     */
    public function map($function)
    {
        if ($this->isDefined()) {
            return new Some(call_user_func($function, $this->get()));
        }
        return $this;
    }

    /**
     * Gets the contained value or a defined default
     *
     * @param mixed $default
     * @return mixed
     */
    public function getOrElse($default)
    {
        if ($this->isDefined()) {
            return $this->get();
        }
        return $default;
    }

    /**
     * Convert this potentially empty container into a full container
     *
     * @param callable $function
     * @return Some
     */
    public function orElse($function)
    {
        if ($this->isEmpty()) {
            return new Some(call_user_func($function));
        }
        return $this;
    }
}

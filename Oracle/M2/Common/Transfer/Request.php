<?php

namespace Oracle\M2\Common\Transfer;

/**
 * Interface that defines a transferable request entity
 *
 * @author Philip Cali <philip.cali@oracle.com>
 */
interface Request
{
    const GET = 'GET';
    const POST = 'POST';
    const PUT = 'PUT';
    const DELETE = 'DELETE';

    /**
     * Add/set a new transfer header
     *
     * @param string $name
     * @param mixed $value
     * @return Request
     */
    public function header($name, $value);

    /**
     * Add/set a new transfer POST param
     *
     * @param string $name
     * @param mixed $value
     * @return Request
     */
    public function param($name, $value);

    /**
     * Add/set a new transfer query parameter
     *
     * @param string $name
     * @param mixed $value
     * @return Request
     */
    public function query($name, $value);

    /**
     * Some raw data to send
     *
     * @param string $data
     */
    public function body($data);

    /**
     * Completes the built request and returns a
     * Response entity to interact with
     *
     * @return Response
     */
    public function respond();
}

<?php

namespace Oracle\M2\Common\Transfer\Curl;

/**
 * Simple response object implementation that
 * reads results from the cURL response
 *
 * @author Philip Cali <philip.cali@oracle.com>
 */
class Response implements \Oracle\M2\Common\Transfer\Response
{
    private $_results;
    private $_headers;
    private $_info;

    /**
     * Everything needed to read results
     *
     * @param string $content
     * @param array $info
     */
    public function __construct($content, $info)
    {
        list($results, $headers) = $this->_parseHeaders($content, $info);
        $this->_results = $results;
        $this->_headers = $headers;
        $this->_info = new \Oracle\M2\Common\DataObject($info, true);
    }

    /**
     * Parse the response headers from the response body
     *
     * @param string $results
     * @param array $info
     * @return array(string, array)
     */
    protected function _parseHeaders($results, $info)
    {
        $headers = substr($results, 0, $info['header_size']);
        $body = substr($results, $info['header_size']);
        $table = array();
        foreach (preg_split('/\r?\n/', $headers) as $header) {
            if (!preg_match('/\:\s*/', $header)) {
                continue;
            }
            list($name, $value) = preg_split("/\\:\\s*/", $header);
            $table[$name] = $value;
        }
        return array(trim($body), $table);
    }


    /**
     * @see parent
     */
    public function body()
    {
        return $this->_results;
    }

    /**
     * @see parent
     */
    public function header($name)
    {
        return $this->_headers[$name];
    }

    /**
     * @see parent
     */
    public function code()
    {
        return $this->_info->getHttpCode();
    }

    /**
     * Gets the cURL info for the transfer
     *
     * @return \Oracle\M2\Common\DataObject
     */
    public function info()
    {
        return $this->_info;
    }
}

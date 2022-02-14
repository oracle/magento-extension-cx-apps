<?php

namespace Oracle\M2\Common\Transfer\Curl;

/**
 * Simple request builder implementation that builds
 * and completes a cURL request to a server.
 *
 * @author Philip Cali <philip.cali@oracle.com>
 */
class Request implements \Oracle\M2\Common\Transfer\Request
{
    private $_method;
    private $_uri;
    private $_options;
    private $_curl;
    private $_body;
    private $_headers = array();
    private $_params = array();
    private $_query = array();
    private $_listeners = array();

    /**
     * Create a request builder with a method, uri, and options
     *
     * @param string $method
     * @param string $uri
     * @param \Oracle\M2\Common\DataObject $options
     * @param \Oracle\M2\Common\StandardResource\Proxy $curl
     */
    public function __construct($method, $uri, $options, $curl = null)
    {
        $this->_uri = $uri;
        $this->_method = $method;
        $this->_options = new \Oracle\M2\Common\DataObject($options->toArray());
        if (is_null($curl)) {
            $curl = new \Oracle\M2\Common\StandardResource\Proxy("curl_");
        }
        $this->_curl = $curl;
    }

    /**
     * Add event observers
     *
     * @param string $name
     * @param array $args
     * @return mixed
     */
    public function __call($name, $args)
    {
        $return = array_shift($args);
        if ($name == 'on') {
            $callback = array_shift($args);
            if (!array_key_exists($return, $this->_listeners)) {
                $this->_listeners[$return] = array();
            }
            $this->_listeners[$return][] = $callback;
            return $this;
        } else if (array_key_exists($name, $this->_listeners)) {
            foreach ($this->_listeners[$name] as $callback) {
                $callback($return);
            }
        }
        return $return;
    }

    /**
     * Gets the cURL Options
     *
     * @return \Oracle\M2\Common\DataObject
     */
    public function getOptions()
    {
        return $this->_options;
    }

    /**
     * Gets the proxy handle
     *
     * @return \Oracle\M2\Common\StandardResource\Proxy
     */
    public function getHandle()
    {
        return $this->_curl;
    }

    /**
     * Prepares the underlying cURL request proxy
     *
     * @return self
     */
    public function prepare()
    {
        $this->_curl->init($this->_createUri());
        $this->_prepareCurl();
        $this->_curl->setopt(CURLOPT_RETURNTRANSFER, true);
        $this->_curl->setopt(CURLOPT_HEADER, true);
        $this->_curl->setopt(CURLOPT_ENCODING, "gzip");
        if ($this->_method != self::POST && $this->_method != self::GET) {
            $this->_curl->setopt(CURLOPT_CUSTOMREQUEST, $this->_method);
            $this->_headers[] = "X-HTTP-Method-Override: {$this->_method}";
        }
        if (!empty($this->_headers)) {
            $this->_curl->setopt(CURLOPT_HTTPHEADER, $this->_headers);
        }
        if (!empty($this->_params) || !empty($this->_body)) {
            $this->_curl->setopt(CURLOPT_POSTFIELDS, empty($this->_body) ? $this->_params : $this->_body);
        }
        return $this;
    }

    /**
     * @see parent
     */
    public function header($name, $value)
    {
        $this->_headers[] = "$name: $value";
        return $this;
    }

    /**
     * @see parent
     */
    public function param($name, $value)
    {
        $this->_params[$name] = $value;
        if (!empty($this->_body)) {
            $this->_body = null;
        }
        return $this;
    }

    /**
     * @see parent
     */
    public function query($name, $value)
    {
        $this->_query[$name] = $value;
        return $this;
    }

    /**
     * @see parent
     */
    public function body($data)
    {
        $this->_body = $data;
        if (!empty($this->_params)) {
            $this->_params = array();
        }
        return $this;
    }

    /**
     * Adds the query parameters URL encoded to the base URI
     *
     * @return string
     */
    protected function _createUri()
    {
        $suffix = '';
        if (!empty($this->_query)) {
            if (strpos($this->_uri, '?') === false) {
                $suffix .= '?';
            } else {
                $suffix .= '&';
            }
            $suffix .= http_build_query($this->_query);
        }
        return $this->_uri . $suffix;
    }

    /**
     * Prepares the cURL handle with all of the set options
     */
    protected function _prepareCurl()
    {
        $prefix = "CURLOPT_";
        $pattern = '/([a-z0-9])([A-Z])/';
        foreach ($this->_options->toArray() as $curlOpt => $value) {
            // Assume we got a camelcase without a prefix
            if (!preg_match("/^{$prefix}/", $curlOpt)) {
                $words = preg_replace($pattern, "$1_$2", $curlOpt);
                $curlOpt = $prefix . strtoupper($words);
            }
            if (defined($curlOpt)) {
                $this->_curl->setopt(constant($curlOpt), $value);
            }
        }
    }

    /**
     * Sends the cURL request and reads response
     *
     * @return array(string, array)
     */
    protected function _completeRequest()
    {
        $this->prepare();
        $results = $this->_curl->exec();
        $code = $this->_curl->errno();
        if ($code) {
            $message = $this->_curl->error();
            $this->_curl->close();
            throw $this->error(new \Oracle\M2\Common\Transfer\Exception($message, $code, $this));
        }
        $info = $this->_curl->getinfo();
        $this->_curl->close();
        return array($results, $info);
    }

    /**
     * @see parent
     */
    public function respond()
    {
        list($content, $info) = $this->_completeRequest();
        return $this->complete(new Response($content, $info));
    }
}

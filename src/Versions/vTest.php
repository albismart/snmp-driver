<?php

namespace Albismart\Versions;

class vTest
{
    public $host;
    public $credentials;

    public function __construct($host, $credentials)
    {
        $this->host = $host;
        $this->credentials = $credentials;
    }

    public function get($oid, $config = [])
    {
        $credentials = is_array($this->credentials) ? $this->credentials['read'] : $this->credentials;
        $timeout = $config['timeout'];
        $retries = $config['retries'];

        return info("snmpget", [$this->host, $credentials, $oid, $timeout, $retries]);
    }

    public function walk($oid, $config = [])
    {
        $credentials = is_array($this->credentials) ? $this->credentials['read'] : $this->credentials;
        $timeout = $config['timeout'];
        $retries = $config['retries'];

        return (array)info("snmpwalk", [$this->host, $credentials, $oid, $timeout, $retries]);
    }

    public function realwalk($oid, $config = [])
    {
        $credentials = is_array($this->credentials) ? $this->credentials['read'] : $this->credentials;
        $timeout = $config['timeout'];
        $retries = $config['retries'];

        return (array)info("snmprealwalk", [$this->host, $credentials, $oid, $timeout, $retries]);
    }

    public function write($oid, $dataType, $value, $config = [])
    {
        $credentials = is_array($this->credentials) ? $this->credentials['write'] : $this->credentials;
        return info("snmpset", [$this->host, $credentials, $oid, $dataType, $value, $config['timeout'], $config['retries']]);
    }
}

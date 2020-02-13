<?php

namespace Albismart\Versions;

class V1
{
    public $host;
    public $credentials;

    public function __construct($host, $credentials)
    {
        $this->host = $host;
        $this->credentials = $credentials;
    }
    public function read($oid, $config = [])
    {
        $getMethod = strtolower($config['getMethod']) ?? 'get';
        $credentials = is_array($this->credentials) ? $this->credentials['read'] : $this->credentials;
        $timeout = $config['timeout'];
        $retries = $config['retries'];

        if ($getMethod == 'get') {
            return snmpget($this->host, $credentials, $oid, $timeout, $retries);
        }
        if ($getMethod == 'walk') {
            return snmpwalk($this->host, $credentials, $oid, $timeout, $retries);
        }
        if ($getMethod == 'realwalk') {
            return snmprealwalk($this->host, $credentials, $oid, $timeout, $retries);
        }
    }

    public function write($oid, $dataType, $value, $config = [])
    {
        $credentials = is_array($this->credentials) ? $this->credentials['write'] : $this->credentials;
        return snmpset($this->host, $credentials, $oid, $dataType, $value, $config['timeout'], $config['retries']);
    }
}

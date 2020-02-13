<?php

namespace Albismart\Versions;

class V2
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
            return snmp2_get($this->host, $credentials, $oid, $timeout, $retries);
        }
        if ($getMethod == 'walk') {
            return snmp2_walk($this->host, $credentials, $oid, $timeout, $retries);
        }
        if ($getMethod == 'realwalk') {
            return snmp2_real_walk($this->host, $credentials, $oid, $timeout, $retries);
        }
    }

    public function write($oid, $dataType, $value, $config = [])
    {
        $credentials = is_array($this->credentials) ? $this->credentials['write'] : $this->credentials;
        return snmp2_set($this->host, $credentials, $oid, $dataType, $value, $config['timeout'], $config['retries']);
    }
}

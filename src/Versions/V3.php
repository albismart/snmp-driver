<?php

namespace Albismart\Versions;

class V3
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
        $method = 'get';
        if ($getMethod == 'get') {
            $method = 'snmp3_get';
        }
        if ($getMethod == 'walk') {
            $method = 'snmp3_walk';
        }
        if ($getMethod == 'realwalk') {
            $method = 'snmp3_real_walk';
        }

        return $this->call_func($method, $config, $oid, $timeout, $retries);
    }

    protected function call_func($function, $config, ...$args)
    {
        return call_user_func($function,
            $this->host,
            $this->credentials['sec_name'],
            $this->credentials['sec_level'] ?? $config['v3']['sec_level'],
            $this->credentials['auth_protocol'] ?? $config['v3']['auth_protocol'],
            $this->credentials['auth_passphrase'],
            $this->credentials['priv_protocol'] ?? $config['v3']['priv_protocol'],
            $this->credentials['priv_passphrase'] ?? $this->credentials['auth_passphrase'],
            ...$args
        );
    }

    public function write($oid, $dataType, $value, $config = [])
    {
        $credentials = is_array($this->credentials) ? $this->credentials['write'] : $this->credentials;
        return $this->call_func('snmp3_set', $config, $oid, $dataType, $value, $config['timeout'], $config['retries']);
    }
}

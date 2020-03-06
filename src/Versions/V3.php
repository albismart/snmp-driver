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

    public function get($oid, $config = [])
    {
        $credentials = is_array($this->credentials) ? $this->credentials['read'] : $this->credentials;
        $timeout = $config['timeout'];
        $retries = $config['retries'];

        return $this->call_func('snmp3_get', $config, $oid, $timeout, $retries);
    }

    public function walk($oid, $config = [])
    {
        $credentials = is_array($this->credentials) ? $this->credentials['read'] : $this->credentials;
        $timeout = $config['timeout'];
        $retries = $config['retries'];

        return $this->call_func('snmp3_walk', $config, $oid, $timeout, $retries);
    }

    public function realwalk($oid, $config = [])
    {
        $credentials = is_array($this->credentials) ? $this->credentials['read'] : $this->credentials;
        $timeout = $config['timeout'];
        $retries = $config['retries'];

        return $this->call_func('snmp3_real_walk', $config, $oid, $timeout, $retries);
    }

    public function write($oid, $dataType, $value, $config = [])
    {
        $credentials = is_array($this->credentials) ? $this->credentials['write'] : $this->credentials;
        return $this->call_func('snmp3_set', $config, $oid, $dataType, $value, $config['timeout'], $config['retries']);
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
}

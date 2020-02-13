<?php

namespace Albismart\Versions;

class V1
{
    public $host;
    public $community;

    public function __construct($host, $community)
    {
        $this->host = $host;
        $this->community = $community;
    }
    public function read($oid, $config = [])
    {
        $getMethod = strtolower($config['getMethod']) ?? 'get';
        $community = is_array($this->community) ? $this->community['read'] : $this->community;
        $timeout = $config['timeout'] ?? 1000000;
        $retries = $config['retries'] ?? 5;

        if ($getMethod == 'get') {
            return snmpget($this->host, $community, $oid, $timeout, $retries);

        }
        if ($getMethod == 'walk') {
            return snmpwalk($this->host, $community, $oid, $timeout, $retries);
        }
        if ($getMethod == 'realwalk') {
            return snmprealwalk($this->host, $community, $oid, $timeout, $retries);
        }
    }

    public function write($oid, $dataType, $value, $config = [])
    {
        $community = is_array($this->community) ? $this->community['write'] : $this->community;
        $timeout = $config['timeout'] ?? 1000000;
        $retries = $config['retries'] ?? 5;
        return snmpset($this->host, $community, $oid, $dataType, $value);
    }
}

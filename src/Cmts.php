<?php

namespace Albismart;

use Albismart\Connection;
use Illuminate\Support\Facades\Config;

class Cmts
{
    /**
     * @var \Albismart\Connection
     */
    public $connection;

    public static function connect($host, $credentials = [], $config = [])
    {
        $cmtsConfig = Config::get("snmp.cmtses.$host");

        if (null !== $cmtsConfig) {
            $host = $cmtsConfig['host'] ?? $host;
            $credentials = $cmtsConfig['credentials'] ?? $credentials;
            unset($cmtsConfig['host'], $cmtsConfig['credentials']);
            $config = array_merge_recursive($cmtsConfig, $config);
        }

        return new static(new Connection($host, $credentials, $config));

    }
    private function __construct($connection)
    {
        $this->connection = $connection;
    }
}

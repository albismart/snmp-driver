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

        if ($cmtsConfig !== null) {
            $host = $cmtsConfig['host'] ?? $host;
            $credentials = $cmtsConfig['credentials'] ?? $credentials;
            unset($cmtsConfig['host'], $cmtsConfig['credentials']);
            $config = array_merge_recursive($cmtsConfig, $config);
        }

        return new static(new Connection($host, $credentials, $config));

    }

    /**
     * Cmts constructor.
     * @param Connection $connection
     */
    private function __construct($connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param $method
     * @param $parameters
     * @return mixed
     */
    protected function __call($method, $parameters)
    {
        if (method_exists($this->connection, $method)) {
            return $this->connection->{$method}(...$parameters);
        }

        throw new \BadMethodCallException(sprintf(
            'Call to undefined method %s::%s()', static::class, $method
        ));
    }

    /**
     * @return array
     */
    public function __debugInfo()
    {
        return $this->connection->__debugInfo();
    }
}

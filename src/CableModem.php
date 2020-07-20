<?php

namespace Albismart;

class CableModem
{
    /**
     * @var \Albismart\Connection
     */
    public $connection;

    public static function connect($host, $credentials = [], $config = [])
    {
        return new static(new Connection($host, $credentials, $config));

    }
    private function __construct($connection)
    {
        $this->connection = $connection;
    }
}

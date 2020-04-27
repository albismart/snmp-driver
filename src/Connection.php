<?php

namespace Albismart;

use Albismart\Versions\V1;
use Albismart\Versions\V2;
use Albismart\Versions\V3;
use Illuminate\Support\Arr;
use Albismart\Versions\vTest;
use Illuminate\Support\Facades\Config;

class Connection
{
    public $host;
    public $credentials;

    protected $adapter;

    public $config = [
        'version' => 'v1',
        'readValueMethod' => SNMP_VALUE_PLAIN,
    ];

    protected static $aliases = [];

    protected static $fake = false;

    /**
     *
     * @param string $host host to connect to the device.
     * @param string|array $credentials comunity password.
     * @param array  $config
     */
    public function __construct($host, $credentials, $config = [])
    {
        $this->host = $host;
        if (is_array($credentials) && (!array_key_exists('read', $credentials) || !array_key_exists('write', $credentials))) {
            throw new \Exception('credentials are not set.');
        }
        $this->credentials = $credentials;
        // set configuration.
        $this->setConfig(Config::get('snmp'))->setConfig($config);
        $this->setVersion($this->config['version'] ?? 'v1');
    }

    public function read($oid)
    {
        return $this->call($oid);
    }

    public function get($oid)
    {
        return $this->call($oid, 'get');
    }

    public function walk($oid)
    {
        return $this->call($oid, 'walk');
    }

    public function realwalk($oid)
    {
        return $this->call($oid, 'realwalk');
    }

    /**
     * @example $this->read(['oid or alias', 'oid or alias']);
     * @example  $this->read('oid or alias');
     * @param  string|array oid or alias
     * @return mixed
     */
    public function call($oid, $method = null)
    {
        if (is_array($oid)) {
            $response = [];
            foreach ($oid as $key => $id) {
                $response[$key] = $this->call($id, $method);
            }
            return $response;
        }
        // find alias and replace
        $oid = $this->findAlias($oid);

        snmp_set_valueretrieval($this->config['readValueMethod']);

        if (is_string($oid)) {
            [$oid, $m] = $this->parseMethod($oid);
            return $this->tryCall($method ?: $m, $oid, $this->config);
        }

        if (is_array($oid)) {
            $responses = [];
            foreach ($oid as $key => $i) {
                [$i, $m] = $this->parseMethod($i);
                $responses[$key] = $this->tryCall($method ?: $m, $i, $this->config);
            }
            return $responses;
        }
    }

    protected function tryCall($method, ...$args)
    {
        try{
            return $this->adapter->$method(...$args);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @example $driver->write('example oid', 'value', 's');
     * @example $driver->write(['example oid' => ['type' => 's', 'value' => 1], ['oid' => 'example oid', 'type' => 's', 'value' => 1]]);
     * @param  mixed $oid
     * @param  string $type
     * @param  mixed $value
     * @return bool|int
     */
    public function write($oidCommand, $type, $value)
    {
        if (!is_array($oidCommand)) {
            $oid = $this->findAlias($oidCommand);
            $this->performWrite($oid, $type, $value);
            return $this;
        }

        foreach ($oidCommand as $key => $data) {
            $oid = $this->findAlias($data['oid'] ?? $key);
            // default type 's'
            $type = $data['type'] ?? 's';

            $type = $data['value'] ?? $data;

            $this->performWrite($oid, $type, $value);
        }

        return $this;
    }

    protected function performWrite($oid, $type, $value)
    {
        if (!is_array($oid)) {
            return $this->adapter->write($oid, $type, $value, $this->config);
        }

        foreach ($oid as $id) {
            [$id] = $this->parseMethod($id);
            $this->adapter->write($id, $type, $value, $this->config);
        }
        return true;
    }

    /**
     * Find aliases from snmp config.
     * @param  string $oid
     * @param  string|null $index
     * @return
     */
    public function findAlias($oid)
    {
        if(!preg_match('/[a-zA-Z]/', $oid)) return $oid;

        $index = null;
        if (preg_match('/\.?\{(.+)\}/', $oid, $matches)) {
            $index = $matches[1];
            $oid = str_replace($matches[0], '', $oid);
        }

        $alias = Arr::get(static::$aliases, $oid);

        return $this->replaceIndex($alias, $index);
    }

    protected function replaceIndex($oid, $index = null)
    {
        if (is_array($oid)) {
            $oid = Arr::dot($oid);
            foreach ($oid as $key => $id) {
                $oid[$key] = $this->replaceIndex($id, $index);
            }
            return $oid;
        }
        if($index){
            return preg_replace('/\{(.+)\}/', $index, $oid);
        }
        return preg_replace('/\.{(.+)\}/', $index, $oid);
    }

    protected function parseMethod($oid)
    {
        if(preg_match('/\[]/', $oid, $matches)){
            return [str_replace($matches[0], '', $oid), 'walk'];
        } elseif (preg_match('/\[R]/', $oid, $matches)){
            return [str_replace($matches[0], '', $oid), 'realwalk'];
        }
        return [$oid, 'get'];
    }

    public static function useAliases(array $aliases)
    {
        static::$aliases = array_merge(static::$aliases, $aliases);
    }

    /**
     * Set the config.
     * @param array $config
     * @return $this
     */
    public function setConfig($config)
    {
        if (array_key_exists('aliases', $config)) {
            static::useAliases($config['aliases']);
            unset($config['aliases']);
        }

        $this->config = array_merge($this->config, $config);
        return $this;
    }

    public static function fake()
    {
        static::$fake = true;
    }

    public function setVersion($version)
    {
        if (static::$fake) {
            return $this->adapter = new vTest($this->host, $this->credentials);
        }
        if ($version == 'v1') {
            return $this->adapter = new V1($this->host, $this->credentials);
        }
        if ($version == 'v2' || $version == 'v2c') {
            return $this->adapter = new V2($this->host, $this->credentials);
        }
        if ($version == 'v3') {
            return $this->adapter = new V3($this->host, $this->credentials);
        }
    }
}

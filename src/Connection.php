<?php

namespace Albismart;

use Albismart\Versions\V1;
use Albismart\Versions\V2;
use Albismart\Versions\V3;
use Illuminate\Support\Arr;

class Connection
{
    public $host;
    public $credentials;

    protected $adapter;

    public $config = [
        'version' => 'v1',
        'readValueMethod' => SNMP_VALUE_PLAIN,
        'getMethod' => 'get', // get, walk, realwalk
    ];

    protected static $aliases = [];

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
        $this->setConfig(app('config')->get('snmp'))->setConfig($config);
        $this->setVersion($this->config['version']);
    }

    /**
     * @example $this->read(['oid or alias', 'oid or alias']);
     * @example  $this->read('oid or alias');
     * @param  string|array oid or alias
     * @return mixed
     */
    public function read($oid)
    {
        if (is_array($oid)) {
            $response = [];
            foreach ($oid as $key => $id) {
                $response[$key] = $this->read($id);
            }
            return $response;
        }
        // find alias and replace
        $oid = $this->findAlias($oid);

        snmp_set_valueretrieval($this->config['readValueMethod']);

        if (is_string($oid)) {
            return $this->adapter->read($oid, $this->config);
        }

        if (is_array($oid)) {
            $responses = [];
            foreach ($oid as $key => $i) {
                $responses[$key] = $this->adapter->read($i, $this->config);
            }
            return $responses;
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
            return $this->performWrite($oid, $type, $value);
        }

        $response = 0;

        foreach ($oidCommand as $key => $data) {
            $oid = $this->findAlias($data['oid'] ?? $key);
            // default type 's'
            $type = $data['type'] ?? 's';

            $type = $data['value'] ?? $data;

            $bool = $this->performWrite($oid, $type, $value);
            if ($bool) $response++;
        }
        return $response;
    }

    protected function performWrite($oid, $type, $value)
    {
        if (!is_array($oid)) {
            return $this->adapter->write($oid, $type, $value, $this->config);
        }

        foreach ($oid as $id) {
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
        if (preg_match('/\{(.+)\}/', $oid, $matches)) {
            $index = $matches[1];
            $oid = str_replace($matches[0], '', $oid);
        }

        $aliases = config('snmp.aliases');
        $alias = Arr::get($aliases, $oid);

        if($alias == null){
            throw new \Exception('Alias is not found.');
        }

        if(preg_match('/\[]/', $alias, $matches)){
            $alias = str_replace($matches[0], '', $alias);
            $this->config['getMethod'] = 'walk';

        } else if(preg_match('/\[R]/', $alias, $matches)){
            $alias = str_replace($matches[0], '', $alias);
            $this->config['getMethod'] = 'realwalk';
        }

        if($index){
            return preg_replace('/\{(.+)\}/', $index, $alias);
        }
        return preg_replace('/\.{(.+)\}/', $index, $alias);

        // future plan make $index support array.
    }

    public static function useAliases(array $aliases)
    {
        static::$aliases = array_merge_recursive(static::$aliases, $aliases);
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

        $this->config = array_merge_recursive($this->config, $config);
        return $this;
    }

    public function setVersion($version)
    {
        if ($version == 'v1') {
            $this->adapter = new V1($this->host, $this->credentials);
        }
        if ($version == 'v2') {
            $this->adapter = new V2($this->host, $this->credentials);
        }
        if ($version == 'v3') {
            $this->adapter = new V3($this->host, $this->credentials);
        }
    }
}

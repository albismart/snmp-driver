<?php

namespace Albismart;

use Carbon\Carbon;
use Albismart\Exceptions\NoResponseException;
use Albismart\Exceptions\SNMPException;
use Albismart\Versions\V1;
use Albismart\Versions\V2;
use Albismart\Versions\V3;
use Illuminate\Support\Arr;
use Albismart\Versions\vTest;
use Illuminate\Support\Facades\{Config};

class Connection
{
    public $host;
    public $credentials;

    protected $adapter;

    public $config = [
        'version' => 'v1',
    ];

    protected static $aliases = [];

    protected static $fake = false;

    /**
     *
     * @param string $host host to connect to the device.
     * @param string|array $credentials comunity password.
     * @param array $config
     * @throws \Exception
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

    /**
     * @param $oid
     * @return mixed
     * @throws NoResponseException
     * @throws SNMPException
     */
    public function read($oid)
    {
        return $this->call($oid);
    }

    /**
     * @param $oid
     * @return mixed
     * @throws NoResponseException
     * @throws SNMPException
     */
    public function get($oid)
    {
        return $this->call($oid, 'get');
    }

    /**
     * @param $oid
     * @return mixed
     * @throws NoResponseException
     * @throws SNMPException
     */
    public function walk($oid)
    {
        return $this->call($oid, 'walk');
    }

    /**
     * @param $oid
     * @return mixed
     * @throws NoResponseException
     * @throws SNMPException
     */
    public function realwalk($oid)
    {
        return $this->call($oid, 'realwalk');
    }

    /**
     * @param $oid
     * @param null $method
     * @return mixed
     * @throws NoResponseException
     * @throws SNMPException
     * @example  $this->read('oid or alias');
     * @example $this->read(['oid or alias', 'oid or alias']);
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

        if (snmp_get_valueretrieval() != SNMP_VALUE_OBJECT) {
            snmp_set_valueretrieval(SNMP_VALUE_OBJECT);
        }

        if (is_string($oid)) {
            [$oid, $m] = $this->parseMethod($oid);
            return $this->tryCall($method ?: $m, $oid, $this->config);
        }

        if (is_array($oid)) {
            $responses = [];
            foreach ($oid as $key => $i) {
                [$i, $m] = $this->parseMethod($i);
                Arr::set($responses, $key, $this->tryCall($method ?: $m, $i, $this->config));
            }
            return $responses;
        }
    }

    protected function tryCall($method, ...$args)
    {
        try{
            return $this->format($this->adapter->$method(...$args));
        } catch (\Exception $e) {
            throw $this->customException($e);
        }
    }

    /**
     * @param $oidCommand
     * @param string $type
     * @param mixed $value
     * @return Connection
     * @example $driver->write('example oid', 'value', 's');
     * @example $driver->write(['example oid' => ['type' => 's', 'value' => 1], ['oid' => 'example oid', 'type' => 's', 'value' => 1]]);
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
     * @param string $oid
     * @return string|string[]|null
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

    /**
     * @param \Exception $e
     * @return NoResponseException|SNMPException
     */
    protected function customException(\Exception $e)
    {
        $message = $e->getMessage();
        switch ($message) {
            case (preg_match('/: No response from /', $message)):
                return new NoResponseException($this->host);
                break;
            default:
                return new SNMPException($e->getMessage(), 500, $e);
        }
    }

    /**
     * @return array
     */
    public function __debugInfo()
    {
        return [
            "host" => $this->host,
            'version' => $this->config['version'] ?? 'v1',
            'fake' => static::$fake,
        ];
    }

    /**
     * @param \stdClass $value
     * @return mixed
     */
    public function format($value)
    {
        if (is_array($value)) {
            $list = [];
            foreach ($value as $key => $item) {
                $list[$key] = $this->format($item);
            }
            return $list;
        }
        if (! snmp_get_quick_print()) {
            [, $parsedValue] = array_pad(explode(':', $value->value, 2), -2, null);
        } else {
            $parsedValue = $value->value;
        }

        $parsedValue = trim(trim($parsedValue), '"');

        switch ($value->type) {
            case SNMP_TIMETICKS:
                preg_match("/\(([[0-9]+)\)/", $parsedValue, $matches);
                return $parsedValue;
//                return Carbon::now()->subMilliseconds($matches[1]*10);
            case SNMP_NULL:
                return null;
            case SNMP_COUNTER:
            case SNMP_UNSIGNED:
            case SNMP_UINTEGER:
            case SNMP_INTEGER:
            case SNMP_COUNTER64:
                return strpos($parsedValue, '.') !== false
                    ? ((float) $parsedValue)
                    : ((int)$parsedValue);
            default:
                return $parsedValue;
        }
    }
}

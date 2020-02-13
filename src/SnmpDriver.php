<?php

namespace Albismart;

use Albismart\Versions\V1;

class SnmpDriver
{
    public $host;
    public $community;

    protected $adapter;

    public $config = [
        'version' => 'v1',
        'readValueMethod' => SNMP_VALUE_PLAIN,
        'getMethod' => 'get', // get, walk, realwalk

    ];

    /**
     *
     * @param string $host host to connect to the device.
     * @param string|array $community comunity password.
     * @param array  $config
     */
    public function __construct($host, $community, $config = [])
    {
        $this->host = $host;
        if (is_array($community) && (!array_key_exists('read', $community) || !array_key_exists('write', $community))) {
            throw new \Exception('community is not set.');
        }
        $this->community = $community;
        $this->config = array_merge($this->config, $config);
        $this->setVersion($this->config['version']);
    }

    public function read($oid, $index = null, $config = [])
    {
        $config = array_merge($this->config, $config);
        // find alias and replace
        $oid = $this->findAlias($oid, $index);

        snmp_set_valueretrieval($config['readValueMethod']);

        if (is_string($oid)) {
            $response = $this->adapter->read($oid, $config);
            // return of parse response
            // return $respons;
        }

        if (is_array($oid)) {
            $responses = [];
            foreach ($oid as $key => $i) {
                $responses[$key] = $this->adapter->read($i, $config);
            }
            // return of parse response
            // return $responses;
        }
    }

    public function readMany($oids, $config = [])
    {
        // [
        //  ['oid' => '', 'index' => ''],
        //  ''
        // ]
    }

    public function write()
    {
        /*
            "{ObjectID}:{DataType}={UpdateValue}"
            [
                '{ObjectID}:{DataType}={UpdateValue}',
                'ObjectID' => '{DataType}={UpdateValue}'
            ]
         */
    }

    /**
     * Find aliases from snmp config.
     * @param  string $oid
     * @param  string|null $index
     * @return
     */
    public function findAlias($oid, $index)
    {
        // find aliases from snmp config.
        // return flat array with dot notation.
        // future plan make $index support array.
    }

    public function setVersion($version)
    {
        if ($version == 'v1') {
            $this->adapter = new V1($this->host, $this->community);
        }
        if ($version == 'v2') {

        }
        if ($version == 'v3') {

        }
    }
}

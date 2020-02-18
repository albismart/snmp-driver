<?php

return [
	'retries' => 5,
	// timeout in microseconds, 1000000 equals 1 second.
	'timeout' => 1000000,

	'v3' => [
		'sec_level' 	=> 'authPriv',
		'auth_protocol' => 'SHA',
		'priv_protocol' => 'AES',
	],

    "cmtses" => [
        // example
        "cmts-1" => [
            "host" => "localhost",
            "version" => "v1",
            "credentials" => [
                "read" => "admin",
                "write" => "admin",
            ]
        ]
    ],
    /*
    |--------------------------------------------------------------------------
    | SNMP aliases
    |--------------------------------------------------------------------------
    |
    */
    'aliases' => [
    ]
];

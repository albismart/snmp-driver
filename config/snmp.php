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

    /*
    |--------------------------------------------------------------------------
    | SNMP mibs
    |--------------------------------------------------------------------------
    |
    */
    'mibs' => [
    ]
];

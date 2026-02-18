<?php

return [
	/*
	|--------------------------------------------------------------------------
	| Default LDAP Connection Name
	|--------------------------------------------------------------------------
	|
	| This connection name will be used by default when using LDAPRecord.
	|
	*/
	'default' => env('LDAP_CONNECTION', 'default'),

	/*
	|--------------------------------------------------------------------------
	| LDAP Connections
	|--------------------------------------------------------------------------
	|
	| Configure one or multiple LDAP connections here.
	|
	*/
	'connections' => [
		'default' => [
			/*
			|------------------------------------------------------------------
			| Hosts
			|------------------------------------------------------------------
			|
			| The server provided a single LDAP host in LDAP_HOST.
			| We also accept LDAP_HOSTS (comma-separated) for backward compat.
			|
			*/
			'hosts'            => array_values(array_filter(
				explode(',', env('LDAP_HOST', '')))
			),
			'base_dn'          => env('LDAP_BASE_DN', ''),
			'username'         => env('LDAP_USERNAME', ''),
			'password'         => env('LDAP_PASSWORD', ''),
			'port'             => (int) env('LDAP_PORT', 389),
			'timeout'          => (int) env('LDAP_TIMEOUT', 5),

			// Use TLS upgrade (STARTTLS on ldap://)
			'use_tls'          => filter_var(env('LDAP_TLS', false), FILTER_VALIDATE_BOOL),

			// Use STARTTLS (alias kept for clarity)
			'use_starttls'     => filter_var(env('LDAP_TLS', false), FILTER_VALIDATE_BOOL),

			// For SSL (ldaps://), set the protocol instead of a boolean.
			// When LDAP_SSL=true we switch protocol to ldaps://, otherwise null (= ldap://).
			'protocol'         => filter_var(env('LDAP_SSL', false), FILTER_VALIDATE_BOOL)
								? 'ldaps://'
								: null,

			// SASL authentication (rarely needed for simple read-only binds)
			'use_sasl'         => filter_var(env('LDAP_SASL', false), FILTER_VALIDATE_BOOL),

			// Custom LDAP options passed to ldap_set_option()
			'options' => [],
		],
	],
];

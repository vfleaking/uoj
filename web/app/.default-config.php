<?php
return [
	'database' => [
		'database'  => 'app_uoj233',
		'username' => 'root',
		'password' => '',
		'host' => '127.0.0.1'
	],
	'web' => [
		'main' => [
			'protocol' => 'http',
			'host' => 'local_uoj.ac',
			'port' => 80
		],
		'blog' => [
			'protocol' => 'http',
			'host' => 'blog.local_uoj.ac',
			'port' => 80
		],
		'domain' => null
	],
	'security' => [
		'user' => [
			'client_salt' => 'salt0'
		],
		'cookie' => [
			'checksum_salt' => ['salt1', 'salt2', 'salt3']
		],
		'recaptcha' => [
			'site_key' => null,
			'secret_key' => null
		]
	],
	'mail' => [
		'noreply' => [
			'username' => 'noreply@none',
			'password' => 'noreply',
			'host' => 'smtp.sina.com',
			'secure' => 'tls',
			'port' => 587
		]
	],
	'judger' => [
		'socket' => [
			'port' => '233',
			'password' => 'password233'
		]
	],
	'svn' => [
		'our-root' => [
			'username' => 'our-root',
			'password' => 'our-root'
		]
	],
	'switch' => [
		'ICP-license' => false,
		'web-analytics' => false
	]
];

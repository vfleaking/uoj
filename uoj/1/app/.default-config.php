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
			'host' => 'local_uoj.ac'
		],
		'blog' => [
			'protocol' => 'http',
			'host' => 'blog.local_uoj.ac'
		]
	],
	'security' => [
		'user' => [
			'client_salt' => 'salt0'
		],
		'cookie' => [
			'checksum_salt' => ['salt1', 'salt2', 'salt3']
		],
	],
	'mail' => [
		'noreply' => [
			'username' => 'noreply@none',
			'password' => 'noreply'
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
	]
];

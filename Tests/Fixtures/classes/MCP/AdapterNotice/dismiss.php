<?php

return [
	'test_data' => [
		'shouldDismissForCurrentUserOnly'          => [
			'config'   => [
				'role'        => 'administrator',
				'valid_nonce' => true,
			],
			'expected' => [
				'success'   => true,
				'dismissed' => true,
			],
		],
		'shouldNotDismissWithInvalidNonce'         => [
			'config'   => [
				'role'        => 'administrator',
				'valid_nonce' => false,
			],
			'expected' => [
				'success'   => false,
				'dismissed' => false,
			],
		],
		'shouldNotDismissForUserWithoutCapability' => [
			'config'   => [
				'role'        => 'subscriber',
				'valid_nonce' => true,
			],
			'expected' => [
				'success'   => false,
				'dismissed' => false,
			],
		],
	],
];

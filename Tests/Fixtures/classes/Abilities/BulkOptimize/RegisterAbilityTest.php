<?php

return [
	'test_data' => [
		'testShouldReturnWPErrorWhenNoPermissions'         => [
			'config'   => [
				'has_permission' => false,
				'args'           => [ 'context' => '' ],
			],
			'expected' => [
				'is_error' => true,
				'has_keys' => [],
			],
		],
		'testShouldReturnErrorArrayWhenPermissionsGranted' => [
			'config'   => [
				'has_permission' => true,
				'args'           => [ 'context' => 'wp' ],
			],
			'expected' => [
				'is_error' => false,
				// The guard's `invalid_api_key`/`insufficient_quota`/`confirmation_required`
				// responses only guarantee a `status` key; only a confirmed, quota-OK,
				// valid-key call reaches do_execute()'s full error/success shape.
				'has_keys' => [ 'status' ],
			],
		],
	],
];

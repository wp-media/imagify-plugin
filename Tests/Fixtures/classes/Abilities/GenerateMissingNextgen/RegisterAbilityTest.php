<?php

return [
	'test_data' => [
		'testShouldReturnWPErrorWhenNoPermissions'                => [
			'config'   => [ 'has_permission' => false ],
			'expected' => [ 'is_error' => true, 'has_keys' => [] ],
		],
		'testShouldReturnScheduledResponseWhenPermissionsGranted' => [
			'config'   => [ 'has_permission' => true ],
			'expected' => [
				'is_error' => false,
				'has_keys' => [ 'status', 'queued_count', 'error_message' ],
			],
		],
	],
];

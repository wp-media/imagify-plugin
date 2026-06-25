<?php

return [
	'test_data' => [
		'testShouldReturnWPErrorWhenNoPermissions'          => [
			'config'   => [ 'has_permission' => false, 'args' => [ 'context' => '' ] ],
			'expected' => [ 'is_error' => true, 'has_keys' => [] ],
		],
		'testShouldReturnErrorArrayWhenPermissionsGranted'  => [
			'config'   => [ 'has_permission' => true, 'args' => [ 'context' => '' ] ],
			'expected' => [
				'is_error' => false,
				'has_keys' => [ 'status', 'context', 'error_message' ],
			],
		],
	],
];

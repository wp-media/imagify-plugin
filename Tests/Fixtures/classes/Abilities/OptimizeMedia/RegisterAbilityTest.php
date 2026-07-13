<?php

return [
	'test_data' => [
		'testShouldReturnWPErrorWhenNoPermissions'          => [
			'config'   => [ 'has_permission' => false, 'args' => [ 'media_id' => 0 ] ],
			'expected' => [ 'is_error' => true, 'has_keys' => [] ],
		],
		'testShouldReturnErrorArrayWhenPermissionsGranted'  => [
			'config'   => [ 'has_permission' => true, 'args' => [ 'media_id' => 0 ] ],
			'expected' => [
				'is_error' => false,
				'has_keys' => [ 'status', 'original_size', 'optimized_size', 'savings_percent', 'error_message' ],
			],
		],
	],
];

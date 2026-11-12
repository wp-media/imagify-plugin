<?php

return [
	'test_data' => [
		'testShouldReturnWPErrorWhenNoPermissions'          => [
			'config'   => [ 'has_permission' => false, 'args' => [ 'media_id' => 0 ] ],
			'expected' => [ 'is_error' => true, 'has_keys' => [] ],
		],
		'testShouldReturnStatusArrayWhenPermissionsGranted' => [
			'config'   => [ 'has_permission' => true, 'args' => [ 'media_id' => 0 ] ],
			'expected' => [
				'is_error' => false,
				'has_keys' => [
					'status',
					'optimization_level',
					'original_size',
					'optimized_size',
					'webp_available',
					'avif_available',
					'error_message',
				],
			],
		],
	],
];

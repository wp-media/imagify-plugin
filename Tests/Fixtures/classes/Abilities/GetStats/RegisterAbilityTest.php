<?php

return [
	'test_data' => [
		'testShouldReturnWPErrorWhenNoPermissions'          => [
			'config'   => [ 'has_permission' => false ],
			'expected' => [ 'is_error' => true, 'has_keys' => [] ],
		],
		'testShouldReturnStatsArrayWhenPermissionsGranted'  => [
			'config'   => [ 'has_permission' => true ],
			'expected' => [
				'is_error' => false,
				'has_keys' => [ 'wp', 'custom-folders' ],
			],
		],
	],
];

<?php

return [
	'test_data' => [
		'testShouldReturnWPErrorWhenNoPermissions'              => [
			'config'   => [ 'has_permission' => false ],
			'expected' => [ 'is_error' => true, 'has_keys' => [] ],
		],
		'testShouldReturnCoverageArrayWhenPermissionsGranted'   => [
			'config'   => [ 'has_permission' => true ],
			'expected' => [
				'is_error' => false,
				'has_keys' => [ 'missing_nextgen_count', 'nextgen_format' ],
			],
		],
	],
];

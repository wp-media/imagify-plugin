<?php

return [
	'test_data' => [
		'testShouldReturnWPErrorWhenNoPermissions'              => [
			'config'   => [ 'has_permission' => false, 'args' => [ 'optimization_level' => 1 ] ],
			'expected' => [ 'is_error' => true, 'has_keys' => [] ],
		],
		'testShouldReturnUpdatedSettingsWhenPermissionsGranted' => [
			'config'   => [ 'has_permission' => true, 'args' => [ 'optimization_level' => 1 ] ],
			'expected' => [
				'is_error' => false,
				'has_keys' => [ 'updated', 'settings' ],
			],
		],
	],
];

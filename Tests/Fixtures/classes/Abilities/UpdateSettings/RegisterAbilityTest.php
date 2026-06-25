<?php

return [
	'test_data' => [
		'testShouldReturnWPErrorWhenNoPermissions'              => [
			'config'   => [ 'has_permission' => false, 'args' => [] ],
			'expected' => [ 'is_error' => true, 'has_keys' => [] ],
		],
		'testShouldReturnUpdatedSettingsWhenPermissionsGranted' => [
			'config'   => [ 'has_permission' => true, 'args' => [] ],
			'expected' => [
				'is_error' => false,
				'has_keys' => [ 'updated', 'settings' ],
			],
		],
	],
];

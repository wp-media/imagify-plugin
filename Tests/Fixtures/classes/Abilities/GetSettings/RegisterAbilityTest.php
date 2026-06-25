<?php

return [
	'test_data' => [
		'testShouldReturnWPErrorWhenNoPermissions' => [
			'config'   => [
				'has_permission' => false,
			],
			'expected' => [
				'is_error' => true,
				'has_keys' => [],
			],
		],
		'testShouldReturnSettingsArrayWhenPermissionsGranted' => [
			'config'   => [
				'has_permission' => true,
			],
			'expected' => [
				'is_error' => false,
				'has_keys' => [
					'optimization_level',
					'backup',
					'auto_optimize',
					'convert_to_webp',
					'convert_to_avif',
					'optimization_format',
				],
			],
		],
	],
];

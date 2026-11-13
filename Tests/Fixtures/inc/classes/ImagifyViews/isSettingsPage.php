<?php

// Single-site install: the settings page lives under options-general.php.
return [
	'test_data' => [
		'shouldReturnTrueOnSettingsPage' => [
			'config'   => [
				'pagenow' => 'options-general.php',
				'page'    => 'imagify',
			],
			'expected' => true,
		],

		'shouldReturnFalseWithoutPageArgument' => [
			'config'   => [
				'pagenow' => 'options-general.php',
				'page'    => null,
			],
			'expected' => false,
		],

		'shouldReturnFalseOnAnotherPage' => [
			'config'   => [
				'pagenow' => 'options-general.php',
				'page'    => 'imagify-bulk-optimization',
			],
			'expected' => false,
		],

		'shouldReturnFalseOnAnotherScreen' => [
			'config'   => [
				'pagenow' => 'admin.php',
				'page'    => 'imagify',
			],
			'expected' => false,
		],
	],
];

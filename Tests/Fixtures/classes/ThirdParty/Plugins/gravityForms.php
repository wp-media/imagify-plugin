<?php

return [
	'test_data' => [
		'testShouldReturnStylesAndScriptWhenConflictModeIsOn' => [
			'config'   => [
				'is_plugin_active' => true,
				'filter'           => 'gform_noconflict_styles',
			],
			'expected' => [
				'styles'           => [
					'imagify-admin-bar',
					'imagify-admin',
					'imagify-notices',
					'imagify-pricing-modal',
				],
				'scripts'           => [
					'imagify-admin-bar',
					'imagify-admin',
					'imagify-notices',
					'imagify-pricing-modal',
					'imagify-sweetalert',
				]
			],
		],
	]
];

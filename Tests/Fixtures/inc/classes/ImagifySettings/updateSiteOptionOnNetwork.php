<?php

return [
	'test_data' => [
		'shouldBailWhenEmptyOptionPage' => [
			'config'   => [
				'option_page'     => '',
				'user_can'        => true,
				'nonce_check'     => true,
				'missing_options' => false,
				'options'         => []
			],
			'expected' => [],
		],

		'shouldBailWhenNotImagifySettingsPage' => [
			'config'   => [
				'option_page'     => 'other-options-page',
				'user_can'        => true,
				'nonce_check'     => true,
				'missing_options' => false,
				'options'         => []
			],
			'expected' => [],
		],

		'shouldDieWhenCurrentUserNotAuthorized' => [
			'config'   => [
				'option_page'     => 'imagify',
				'user_can'        => false,
				'nonce_check'     => true,
				'missing_options' => false,
				'options'         => []
			],
			'expected' => [],
		],

		'shouldDieWhenNonceCheckFails' => [
			'config'   => [
				'option_page'     => 'imagify',
				'user_can'        => true,
				'nonce_check'     => false,
				'missing_options' => false,
				'options'         => []
			],
			'expected' => [],
		],

		'shouldDieWithErrorWhenSettingsNotFound' => [
			'config'   => [
				'option_page'     => 'imagify',
				'user_can'        => true,
				'nonce_check'     => true,
				'missing_options' => true,
				'options'         => []
			],
			'expected' => [
				'die_message' => '<strong>ERROR</strong>: options page not found.',
			],
		],

		'shouldUpdateOptions' => [
			'config'   => [
				'option_page'     => 'imagify',
				'user_can'        => true,
				'nonce_check'     => true,
				'missing_options' => false,
				'options'         => [
					'optimization_level' => '1',
					'backup'             => '1',
					'convert_to_webp'    => '1',
					'disallowed-sizes'   => [ 'thumbnail', 'large', 'medium' ]
				],
			],
			'expected' => [
				'options_count' => 4,
			],
		],
	]
];

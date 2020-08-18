<?php

return [
	'shouldBailWhenEmptyOptionPage' => [
		'config'   => [
			'option_page' => ''
		],
		'expected' => [],
	],

	'shouldBailWhenNotImagifySettingsPage' => [
		'config'   => [
			'option_page' => 'other-options-page',
		],
		'expected' => [],
	],

	'shouldBailWhenCurrentUserNotAuthorized' => [
		'config'   => [
			'option_page' => 'imagify',
			'user_can'    => false,
		],
		'expected' => [],
	],

	'shouldBailWhenNonceCheckFails' => [
		'config'   => [
			'option_page' => 'imagify',
			'user_can'    => true,
			'nonce_check' => false,
		],
		'expected' => [],
	],

	'shouldDieWithErrorWhenSettingsNotFound' => [
		'config'   => [
			'option_page'     => 'imagify',
			'user_can'        => true,
			'nonce_check'     => true,
			'missing_options' => true,
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
				'optimization_level' => 1,
				'backup'             => 1,
				'convert_to_webp'    => 1,
				'disallowed-sizes'   => [ 'thumbnail', 'large', 'medium' ]
			]
		],
		'expected' => [
			'options_count' => 4,
		],
	],
];

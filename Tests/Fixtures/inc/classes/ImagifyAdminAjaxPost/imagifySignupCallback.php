<?php

return [
	'test_data' => [
		'shouldSendAPlusAliasToTheApiUnchanged'         => [
			'config'   => [
				'email' => 'hanna+may21@wp-media.me',
			],
			'expected' => [
				'is_error'      => false,
				'body_contains' => 'hanna+may21@wp-media.me',
			],
		],

		'shouldSendDotsAndUnderscoresToTheApiUnchanged' => [
			'config'   => [
				'email' => 'film.simleu_21@gmail.com',
			],
			'expected' => [
				'is_error'      => false,
				'body_contains' => 'film.simleu_21@gmail.com',
			],
		],

		'shouldTrimSurroundingWhitespaceInsteadOfRejecting' => [
			'config'   => [
				'email' => '  tzinkeh@yahoo.no  ',
			],
			'expected' => [
				'is_error'      => false,
				'body_contains' => 'tzinkeh@yahoo.no',
			],
		],

		'shouldRejectAnAddressSanitizationWouldAlter'   => [
			'config'   => [
				'email' => 'hanna may21@wp-media.me',
			],
			'expected' => [
				'is_error'      => true,
				'error_message' => 'Not a valid email address.',
			],
		],

		'shouldRejectAnInvalidAddress'                  => [
			'config'   => [
				'email' => 'not-an-email',
			],
			'expected' => [
				'is_error'      => true,
				'error_message' => 'Not a valid email address.',
			],
		],
	],
];

<?php

return [
	'test_data' => [
		'shouldReadContextFromGetByDefault' => [
			'config'   => [
				'get'  => [ 'context' => 'WP' ],
				'post' => [],
				'args' => [],
			],
			'expected' => 'wp',
		],

		'shouldReadContextFromPostWhenAsked' => [
			'config'   => [
				'get'  => [ 'context' => 'wp' ],
				'post' => [ 'context' => 'custom-folders' ],
				'args' => [ 'POST' ],
			],
			'expected' => 'custom-folders',
		],

		'shouldReadTheGivenParameter' => [
			'config'   => [
				'get'  => [],
				'post' => [ 'folder' => 'custom-folders' ],
				'args' => [ 'POST', 'folder' ],
			],
			'expected' => 'custom-folders',
		],

		'shouldReturnNoopWhenMissing' => [
			'config'   => [
				'get'  => [],
				'post' => [],
				'args' => [],
			],
			'expected' => 'noop',
		],

		'shouldReturnNoopWhenOnlySentWithTheOtherMethod' => [
			'config'   => [
				'get'  => [],
				'post' => [ 'context' => 'wp' ],
				'args' => [],
			],
			'expected' => 'noop',
		],

		'shouldReturnNoopWhenEmpty' => [
			'config'   => [
				'get'  => [ 'context' => '' ],
				'post' => [],
				'args' => [],
			],
			'expected' => 'noop',
		],

		'shouldReturnNoopWhenNotAString' => [
			'config'   => [
				'get'  => [ 'context' => [ 'wp' ] ],
				'post' => [],
				'args' => [],
			],
			'expected' => 'noop',
		],
	],
];

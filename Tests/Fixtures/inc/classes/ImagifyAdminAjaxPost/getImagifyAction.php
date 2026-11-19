<?php

return [
	'test_data' => [
		'shouldReadActionFromGetByDefault' => [
			'config'   => [
				'get'  => [ 'imagify_action' => 'restore' ],
				'post' => [],
				'args' => [],
			],
			'expected' => 'restore',
		],

		'shouldReadActionFromPostWhenAsked' => [
			'config'   => [
				'get'  => [],
				'post' => [ 'imagify_action' => 'restore' ],
				'args' => [ 'POST' ],
			],
			'expected' => 'restore',
		],

		'shouldDefaultToOptimizeWhenMissing' => [
			'config'   => [
				'get'  => [],
				'post' => [ 'imagify_action' => 'restore' ],
				'args' => [],
			],
			'expected' => 'optimize',
		],

		'shouldDefaultToOptimizeWhenEmpty' => [
			'config'   => [
				'get'  => [ 'imagify_action' => '' ],
				'post' => [],
				'args' => [],
			],
			'expected' => 'optimize',
		],
	],
];

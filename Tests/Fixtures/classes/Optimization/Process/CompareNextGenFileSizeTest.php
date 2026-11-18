<?php

return [
	'test_data' => [
		// Default (no filter): a next-gen file heavier than its optimized non-next-gen version is deleted.
		'shouldDeleteHeavierNextGenByDefault'           => [
			'config'   => [
				'filter_value'   => null,
				'optimized_size' => 1000,
				'new_size'       => 1500,
			],
			'expected' => [
				'deleted'  => true,
				'is_error' => true,
			],
		],

		// Default (no filter): a lighter next-gen file is kept.
		'shouldKeepLighterNextGenByDefault'             => [
			'config'   => [
				'filter_value'   => null,
				'optimized_size' => 1000,
				'new_size'       => 800,
			],
			'expected' => [
				'deleted'  => false,
				'is_error' => false,
			],
		],

		// Opt-in through the filter: a heavier next-gen file is kept.
		'shouldKeepHeavierNextGenWhenFilterReturnsTrue' => [
			'config'   => [
				'filter_value'   => true,
				'optimized_size' => 1000,
				'new_size'       => 1500,
			],
			'expected' => [
				'deleted'  => false,
				'is_error' => false,
			],
		],
	],
];

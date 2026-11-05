<?php
/**
 * Core::core_handler() rejects users who can't use Imagifybeat before looking at the nonce.
 */

return [
	'test_data' => [
		'shouldRejectWithoutNonce'            => [
			'config'   => [
				'allowed' => [],
				'post'    => [],
			],
			'expected' => [ null, 403 ],
		],
		'shouldRejectWithInvalidNonce'        => [
			'config'   => [
				'allowed' => [],
				'post'    => [ '_nonce' => 'x' ],
			],
			'expected' => [ null, 403 ],
		],
		'shouldRejectWithNonceAndData'        => [
			'config'   => [
				'allowed' => [ 'wp:manual-optimize' ],
				'post'    => [
					'_nonce' => 'abcdef0123',
					'data'   => [ 'imagify_requirements' => 1 ],
				],
			],
			'expected' => [ null, 403 ],
		],
		'shouldReachTheNonceCheckWhenAllowed' => [
			'config'   => [
				'allowed' => [ 'wp:optimize' ],
				'post'    => [],
			],
			'expected' => [],
		],
	],
];

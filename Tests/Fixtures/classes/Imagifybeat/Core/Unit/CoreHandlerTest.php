<?php
/**
 * Core::core_handler() rejects users who can't use Imagifybeat before looking at the nonce, and before any Imagifybeat
 * hook fires. 'error' / 'json' are the arguments sent (null: not sent, 'json' without 'server_time'), 'hooks' how many
 * times each hook fired.
 */

$no_hooks = [
	'imagifybeat_refresh_nonces' => 0,
	'imagifybeat_received'       => 0,
	'imagifybeat_send'           => 0,
	'imagifybeat_tick'           => 0,
];

return [
	'test_data' => [
		'shouldRejectWithoutNonce'                    => [
			'config'   => [
				'allowed'     => [],
				'post'        => [],
				'nonce_state' => 1,
			],
			'expected' => [
				'error' => [ null, 403 ],
				'json'  => null,
				'hooks' => $no_hooks,
			],
		],
		'shouldRejectWithInvalidNonce'                => [
			'config'   => [
				'allowed'     => [],
				'post'        => [ '_nonce' => 'x' ],
				'nonce_state' => false,
			],
			'expected' => [
				'error' => [ null, 403 ],
				'json'  => null,
				'hooks' => $no_hooks,
			],
		],
		'shouldRejectWithValidNonceAndData'           => [
			'config'   => [
				'allowed'     => [ 'wp:manual-optimize' ],
				'post'        => [
					'_nonce' => 'abcdef0123',
					'data'   => [ 'imagify_requirements' => 1 ],
				],
				'nonce_state' => 1,
			],
			'expected' => [
				'error' => [ null, 403 ],
				'json'  => null,
				'hooks' => $no_hooks,
			],
		],
		'shouldReachTheNonceCheckWhenAllowed'         => [
			'config'   => [
				'allowed'     => [ 'wp:optimize' ],
				'post'        => [],
				'nonce_state' => 1,
			],
			'expected' => [
				'error' => [],
				'json'  => null,
				'hooks' => $no_hooks,
			],
		],
		'shouldFireReceivedAndSendWhenAllowed'        => [
			'config'   => [
				'allowed'     => [ 'wp:manage' ],
				'post'        => [
					'_nonce' => 'abcdef0123',
					'data'   => [ 'imagify_requirements' => 1 ],
				],
				'nonce_state' => 1,
			],
			'expected' => [
				'error' => null,
				'json'  => [],
				'hooks' => [
					'imagifybeat_refresh_nonces' => 0,
					'imagifybeat_received'       => 1,
					'imagifybeat_send'           => 1,
					'imagifybeat_tick'           => 1,
				],
			],
		],
		'shouldRefreshNoncesWhenAllowedAndNonceIsOld' => [
			'config'   => [
				'allowed'     => [ 'wp:manage' ],
				'post'        => [
					'_nonce' => 'abcdef0123',
					'data'   => [ 'imagify_requirements' => 1 ],
				],
				'nonce_state' => 2,
			],
			'expected' => [
				'error' => null,
				'json'  => [],
				'hooks' => [
					'imagifybeat_refresh_nonces' => 1,
					'imagifybeat_received'       => 1,
					'imagifybeat_send'           => 1,
					'imagifybeat_tick'           => 1,
				],
			],
		],
		'shouldOnlyRefreshNoncesWhenNonceExpired'     => [
			'config'   => [
				'allowed'     => [ 'wp:manage' ],
				'post'        => [
					'_nonce' => 'abcdef0123',
					'data'   => [ 'imagify_requirements' => 1 ],
				],
				'nonce_state' => false,
			],
			'expected' => [
				'error' => null,
				'json'  => [ 'nonces_expired' => true ],
				'hooks' => [
					'imagifybeat_refresh_nonces' => 1,
					'imagifybeat_received'       => 0,
					'imagifybeat_send'           => 0,
					'imagifybeat_tick'           => 0,
				],
			],
		],
	],
];

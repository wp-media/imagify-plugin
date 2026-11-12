<?php

return [
	'test_data' => [

		// ── Guard: opt-in disabled ────────────────────────────────────────────
		'noEventWhenOptInDisabled' => [
			'config'   => [
				'can_track' => false,
				'response'  => true,
				'data'      => [],
				'context'   => 'wp',
			],
			'expected' => [
				'track_direct_called' => false,
			],
		],

		// ── Guard: WP_Error response ──────────────────────────────────────────
		'noEventWhenResponseIsWpError' => [
			'config'   => [
				'can_track' => true,
				'response'  => 'wp_error',
				'data'      => [],
				'context'   => 'wp',
			],
			'expected' => [
				'track_direct_called' => false,
			],
		],

		// ── Happy path: basic success ─────────────────────────────────────────
		'happyPathFiresMediaRestored' => [
			'config'   => [
				'can_track' => true,
				'response'  => true,
				'data'      => [ 'level' => 1, 'sizes' => [] ],
				'context'   => 'wp',
			],
			'expected' => [
				'track_direct_called' => true,
				'media_context'       => 'wp',
				'optimization_level'  => 1,
				'next_gen_format'     => null,
			],
		],

		// ── optimization_level: false stored value ────────────────────────────
		'optimizationLevelNullWhenLevelIsFalse' => [
			'config'   => [
				'can_track' => true,
				'response'  => true,
				'data'      => [ 'level' => false, 'sizes' => [] ],
				'context'   => 'wp',
			],
			'expected' => [
				'track_direct_called' => true,
				'optimization_level'  => null,
				'next_gen_format'     => null,
			],
		],

		// ── optimization_level: key absent ───────────────────────────────────
		'optimizationLevelNullWhenLevelMissing' => [
			'config'   => [
				'can_track' => true,
				'response'  => true,
				'data'      => [ 'sizes' => [] ],
				'context'   => 'wp',
			],
			'expected' => [
				'track_direct_called' => true,
				'optimization_level'  => null,
				'next_gen_format'     => null,
			],
		],

		// ── next_gen_format: AVIF succeeds ───────────────────────────────────
		'nextGenFormatIsAvifWhenAvifSucceeds' => [
			'config'   => [
				'can_track' => true,
				'response'  => true,
				'data'      => [
					'level' => 1,
					'sizes' => [ 'full@imagify-avif' => [ 'success' => true ] ],
				],
				'context'   => 'wp',
			],
			'expected' => [
				'track_direct_called' => true,
				'next_gen_format'     => 'avif',
			],
		],

		// ── next_gen_format: WebP succeeds (AVIF absent) ─────────────────────
		'nextGenFormatIsWebpWhenOnlyWebpSucceeds' => [
			'config'   => [
				'can_track' => true,
				'response'  => true,
				'data'      => [
					'level' => 1,
					'sizes' => [ 'full@imagify-webp' => [ 'success' => true ] ],
				],
				'context'   => 'wp',
			],
			'expected' => [
				'track_direct_called' => true,
				'next_gen_format'     => 'webp',
			],
		],

		// ── next_gen_format: none succeeded ──────────────────────────────────
		'nextGenFormatIsNullWhenNoneSucceeded' => [
			'config'   => [
				'can_track' => true,
				'response'  => true,
				'data'      => [ 'level' => 1, 'sizes' => [] ],
				'context'   => 'custom-folders',
			],
			'expected' => [
				'track_direct_called' => true,
				'next_gen_format'     => null,
				'media_context'       => 'custom-folders',
			],
		],
	],
];

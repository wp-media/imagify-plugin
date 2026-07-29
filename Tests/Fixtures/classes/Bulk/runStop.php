<?php

return [
	'test_data' => [
		'shouldReturnNotRunningWhenNothingIsRunningAndQueueIsEmpty' => [
			'config'   => [
				'contexts'   => [ 'wp' ],
				'transients' => [],
				'pending'    => [],
			],
			'expected' => [
				'success'   => false,
				'message'   => 'not-running',
				'cancelled' => 0,
			],
		],

		'shouldCountTheActuallyCancelledActions'          => [
			'config'   => [
				'contexts'   => [ 'wp' ],
				'transients' => [
					'imagify_wp_optimize_running' => [
						'total'     => 10,
						'remaining' => 4,
					],
				],
				'pending'    => [
					'imagify_optimize_media|imagify-wp-optimize-media' => [ 11, 12, 13 ],
				],
			],
			'expected' => [
				'success'   => true,
				'message'   => 'success',
				'cancelled' => 3,
			],
		],

		'shouldIgnoreAStaleRemainingCounter'              => [
			// The transient claims 18 media are left, but the queue is empty: the counter drifted
			// because imagify_after_optimize never fired for the media that bailed out early.
			'config'   => [
				'contexts'   => [ 'wp' ],
				'transients' => [
					'imagify_wp_optimize_running' => [
						'total'     => 20,
						'remaining' => 18,
					],
				],
				'pending'    => [],
			],
			'expected' => [
				'success'   => true,
				'message'   => 'success',
				'cancelled' => 0,
			],
		],

		'shouldSumCancelledActionsAcrossHooksAndContexts' => [
			'config'   => [
				'contexts'   => [ 'wp', 'custom-folders' ],
				'transients' => [
					'imagify_wp_optimize_running' => [
						'total'     => 10,
						'remaining' => 3,
					],
				],
				'pending'    => [
					'imagify_optimize_media|imagify-wp-optimize-media'                 => [ 1, 2 ],
					'imagify_convert_next_gen|imagify-wp-convert-nextgen'              => [ 3 ],
					'imagify_optimize_media|imagify-custom-folders-optimize-media'     => [ 4, 5, 6 ],
					'imagify_convert_next_gen|imagify-custom-folders-convert-nextgen'  => [ 7 ],
				],
			],
			'expected' => [
				'success'   => true,
				'message'   => 'success',
				'cancelled' => 7,
			],
		],

		'shouldSucceedWhenOnlyOrphanActionsRemain'        => [
			// No progress transient (expired), but actions are still queued: still a real stop.
			'config'   => [
				'contexts'   => [ 'wp' ],
				'transients' => [],
				'pending'    => [
					'imagify_optimize_media|imagify-wp-optimize-media' => [ 21, 22 ],
				],
			],
			'expected' => [
				'success'   => true,
				'message'   => 'success',
				'cancelled' => 2,
			],
		],
	],
];

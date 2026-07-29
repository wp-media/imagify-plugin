<?php

return [
	'test_data' => [
		'shouldReturnNotRunningWhenNothingIsRunning'     => [
			'config'   => [
				'contexts'   => [ 'wp' ],
				'transients' => [],
			],
			'expected' => [
				'success'   => false,
				'message'   => 'not-running',
				'cancelled' => 0,
			],
		],

		'shouldCancelRemainingMediaWhenRunning'          => [
			'config'   => [
				'contexts'   => [ 'wp' ],
				'transients' => [
					'imagify_wp_optimize_running' => [
						'total'     => 10,
						'remaining' => 4,
					],
				],
			],
			'expected' => [
				'success'   => true,
				'message'   => 'success',
				'cancelled' => 4,
			],
		],

		'shouldSumRemainingMediaAcrossContexts'          => [
			'config'   => [
				'contexts'   => [ 'wp', 'custom-folders' ],
				'transients' => [
					'imagify_wp_optimize_running' => [
						'total'     => 10,
						'remaining' => 3,
					],
					'imagify_custom-folders_optimize_running' => [
						'total'     => 5,
						'remaining' => 2,
					],
				],
			],
			'expected' => [
				'success'   => true,
				'message'   => 'success',
				'cancelled' => 5,
			],
		],

		'shouldIgnoreIdleContextWhenAnotherOneIsRunning' => [
			'config'   => [
				'contexts'   => [ 'wp', 'custom-folders' ],
				'transients' => [
					'imagify_custom-folders_optimize_running' => [
						'total'     => 9,
						'remaining' => 7,
					],
				],
			],
			'expected' => [
				'success'   => true,
				'message'   => 'success',
				'cancelled' => 7,
			],
		],

		'shouldNeverReportNegativeCancelledCount'        => [
			'config'   => [
				'contexts'   => [ 'wp' ],
				'transients' => [
					'imagify_wp_optimize_running' => [
						'total'     => 10,
						'remaining' => -2,
					],
				],
			],
			'expected' => [
				'success'   => false,
				'message'   => 'not-running',
				'cancelled' => 0,
			],
		],
	],
];

<?php
/**
 * The next-gen generation progress (settings page) is sent to users who can manage Imagify. Media are only counted in
 * contexts the user can bulk-optimize.
 * 'contexts' are the contexts the bulk instances are requested for, 'status' the data sent (null: nothing sent).
 */

return [
	'test_data' => [
		'shouldNotSendWhenNotAsked'                   => [
			'config'   => [
				'allowed' => [ 'wp:manage' ],
				'data'    => [],
				'total'   => 7,
				'format'  => 'webp',
			],
			'expected' => [
				'contexts' => [],
				'status'   => null,
			],
		],
		'shouldNotSendToBulkOptimizationUsers'        => [
			'config'   => [
				'allowed' => [ 'wp:bulk-optimize', 'custom-folders:bulk-optimize' ],
				'data'    => [ 'imagify_options_optimization_status' => [ 'wp' ] ],
				'total'   => 7,
				'format'  => 'webp',
			],
			'expected' => [
				'contexts' => [],
				'status'   => null,
			],
		],
		'shouldNotSendWhenNothingIsGenerated'         => [
			'config'   => [
				'allowed' => [ 'wp:manage' ],
				'data'    => [ 'imagify_options_optimization_status' => [ 'wp' ] ],
				'total'   => false,
				'format'  => 'webp',
			],
			'expected' => [
				'contexts' => [],
				'status'   => null,
			],
		],
		'shouldNotSendWhenNextGenIsOff'               => [
			'config'   => [
				'allowed' => [ 'wp:manage' ],
				'data'    => [ 'imagify_options_optimization_status' => [ 'wp' ] ],
				'total'   => 7,
				'format'  => 'off',
			],
			'expected' => [
				'contexts' => [],
				'status'   => null,
			],
		],
		'shouldSendToManagersIgnoringInvalidContexts' => [
			'config'   => [
				'allowed' => [ 'wp:manage', 'wp:bulk-optimize', 'custom-folders:bulk-optimize' ],
				'data'    => [ 'imagify_options_optimization_status' => [ 'wp', [ 'wp' ], '', 'Custom-Folders' ] ],
				'total'   => 7,
				'format'  => 'webp',
			],
			'expected' => [
				'contexts' => [ 'wp', 'custom-folders' ],
				'status'   => [
					'remaining' => 4,
					'total'     => 7,
				],
			],
		],
		'shouldOnlyCountContextsUserCanBulkOptimize'  => [
			'config'   => [
				'allowed' => [ 'wp:manage', 'wp:bulk-optimize', 'custom-folders:manage' ],
				'data'    => [ 'imagify_options_optimization_status' => [ 'wp', 'custom-folders' ] ],
				'total'   => 7,
				'format'  => 'webp',
			],
			'expected' => [
				'contexts' => [ 'wp' ],
				'status'   => [
					'remaining' => 2,
					'total'     => 7,
				],
			],
		],
	],
];

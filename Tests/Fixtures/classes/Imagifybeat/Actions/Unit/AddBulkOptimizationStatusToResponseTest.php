<?php
/**
 * The bulk status only uses valid contexts the user can bulk-optimize.
 * 'contexts' are the contexts the bulk instances are requested for, 'status' the data sent (null: nothing sent).
 */

$wp_group = [
	'groupID' => 'library',
	'context' => 'wp',
];

return [
	'test_data' => [
		'shouldNotSendWhenNotAsked'                => [
			'config'   => [
				'allowed' => [ 'wp:bulk-optimize' ],
				'data'    => [],
				'running' => [],
			],
			'expected' => [
				'contexts' => [],
				'status'   => null,
			],
		],
		'shouldNotSendWithoutBulkCapability'       => [
			'config'   => [
				'allowed' => [ 'wp:optimize', 'wp:manage' ],
				'data'    => [ 'imagify_bulk_optimization_status' => [ $wp_group ] ],
				'running' => [],
			],
			'expected' => [
				'contexts' => [],
				'status'   => null,
			],
		],
		'shouldNotSendForInvalidContextsOnly'      => [
			'config'   => [
				'allowed' => [ 'wp:bulk-optimize', 'custom-folders:bulk-optimize' ],
				'data'    => [
					'imagify_bulk_optimization_status' => [
						'not-a-group',
						[ 'groupID' => 'library' ],
						[ 'context' => [ 'wp' ] ],
						[ 'context' => '' ],
						[ 'context' => 'bogus' ],
					],
				],
				'running' => [],
			],
			'expected' => [
				'contexts' => [],
				'status'   => null,
			],
		],
		'shouldOnlyUseAllowedValidContexts'        => [
			'config'   => [
				'allowed' => [ 'wp:bulk-optimize' ],
				'data'    => [
					'imagify_bulk_optimization_status' => [
						[ 'context' => [ 'wp' ] ],
						[ 'context' => 'custom-folders' ],
						[ 'context' => '../bogus' ],
						[
							'groupID' => 'library',
							'context' => 'WP',
						],
					],
				],
				'running' => [
					'wp' => [
						'total'     => 4,
						'remaining' => 1,
					],
				],
			],
			'expected' => [
				'contexts' => [ 'wp' ],
				'status'   => [
					'groups_data' => [ 'wp' => [ 'context' => 'wp' ] ],
					'remaining'   => 1,
					'percentage'  => 75.0,
					'result'      => false,
				],
			],
		],
		'shouldSendBothContextsToNetworkBulkUsers' => [
			'config'   => [
				'allowed' => [ 'wp:bulk-optimize', 'custom-folders:bulk-optimize' ],
				'data'    => [
					'imagify_bulk_optimization_status' => [
						$wp_group,
						[
							'groupID' => 'custom-folders',
							'context' => 'custom-folders',
						],
					],
				],
				'running' => [],
			],
			'expected' => [
				'contexts' => [ 'wp', 'custom-folders' ],
				'status'   => [
					'groups_data' => [
						'wp'             => [ 'context' => 'wp' ],
						'custom-folders' => [ 'context' => 'custom-folders' ],
					],
					'remaining'   => 0,
					'percentage'  => 0.0,
					'result'      => false,
				],
			],
		],
	],
];

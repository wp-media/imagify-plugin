<?php
/**
 * Bulk stats are only computed for the folder types ('{groupID}|{context}') the user can bulk-optimize.
 * 'types' is what imagify_get_bulk_stats() receives, null when it is not called.
 */

return [
	'test_data' => [
		'shouldNotSendWhenNotAsked'               => [
			'config'   => [
				'allowed' => [ 'wp:bulk-optimize' ],
				'data'    => [],
			],
			'expected' => [ 'types' => null ],
		],
		'shouldNotSendWhenTypesAreNotAList'       => [
			'config'   => [
				'allowed' => [ 'wp:bulk-optimize' ],
				'data'    => [ 'imagify_bulk_optimization_stats' => 'library|wp' ],
			],
			'expected' => [ 'types' => null ],
		],
		'shouldNotSendWithoutBulkCapability'      => [
			'config'   => [
				'allowed' => [ 'wp:optimize', 'wp:manage' ],
				'data'    => [ 'imagify_bulk_optimization_stats' => [ 'library|wp' ] ],
			],
			'expected' => [ 'types' => null ],
		],
		'shouldSendLibraryStatsToSiteBulkUsers'   => [
			'config'   => [
				'allowed' => [ 'wp:bulk-optimize' ],
				'data'    => [ 'imagify_bulk_optimization_stats' => [ 'library|wp', 'custom-folders|custom-folders' ] ],
			],
			'expected' => [ 'types' => [ 'library|wp' => 0 ] ],
		],
		'shouldSendAllStatsToUsersOfBothContexts' => [
			'config'   => [
				'allowed' => [ 'wp:bulk-optimize', 'custom-folders:bulk-optimize' ],
				'data'    => [ 'imagify_bulk_optimization_stats' => [ 'library|wp', 'custom-folders|custom-folders' ] ],
			],
			'expected' => [
				'types' => [
					'library|wp'                    => 0,
					'custom-folders|custom-folders' => 1,
				],
			],
		],
		'shouldIgnoreInvalidTypes'                => [
			'config'   => [
				'allowed' => [ 'wp:bulk-optimize' ],
				'data'    => [ 'imagify_bulk_optimization_stats' => [ 'nopipe', [ 'library|wp' ], 'a|bogus', '', 'library|wp' ] ],
			],
			'expected' => [ 'types' => [ 'library|wp' => 4 ] ],
		],
	],
];

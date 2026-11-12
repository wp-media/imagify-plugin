<?php
/**
 * The "Other Media" status is sent to users of that page only, for custom folders files they can optimize only.
 * 'returned' lists the files whose columns are sent.
 */

return [
	'test_data' => [
		'shouldNotSendWhenNotAsked'          => [
			'config'   => [
				'allowed'     => [ 'custom-folders:optimize' ],
				'allowed_ids' => [ 1 ],
				'statuses'    => [],
			],
			'expected' => [],
		],
		'shouldNotSendToMediaLibraryUsers'   => [
			'config'   => [
				'allowed'     => [ 'wp:optimize', 'wp:manage', 'wp:bulk-optimize' ],
				'allowed_ids' => [ 1 ],
				'statuses'    => [ 'custom-folders' => [ '_1' => 1 ] ],
			],
			'expected' => [],
		],
		'shouldSendToOtherMediaUsers'        => [
			'config'   => [
				'allowed'     => [ 'custom-folders:optimize' ],
				'allowed_ids' => [ 1 ],
				'statuses'    => [ 'custom-folders' => [ '_1' => 1 ] ],
			],
			'expected' => [ 1 ],
		],
		'shouldSendToNetworkOtherMediaUsers' => [
			'config'   => [
				'allowed'     => [ 'custom-folders:bulk-optimize' ],
				'allowed_ids' => [ 1 ],
				'statuses'    => [ 'custom-folders' => [ '_1' => 1 ] ],
			],
			'expected' => [ 1 ],
		],
		'shouldIgnoreOtherContexts'          => [
			'config'   => [
				'allowed'     => [ 'custom-folders:optimize' ],
				'allowed_ids' => [ 1, 5 ],
				'statuses'    => [
					'wp'  => [ '_5' => 1 ],
					'ngg' => [ '_1' => 1 ],
				],
			],
			'expected' => [],
		],
		'shouldOnlySendAllowedFiles'         => [
			'config'   => [
				'allowed'     => [ 'custom-folders:optimize' ],
				'allowed_ids' => [ 1 ],
				'statuses'    => [
					'custom-folders' => [
						'_1' => 1,
						'_2' => 1,
					],
					'wp'             => [ '_1' => 1 ],
				],
			],
			'expected' => [ 1 ],
		],
	],
];

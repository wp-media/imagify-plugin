<?php

return [
	'test_data' => [
		'shouldAllowWhenUserCanBulkOptimizeRequestedContext' => [
			'config'   => [
				'allowed' => [ 'wp:manage', 'custom-folders:bulk-optimize' ],
				'input'   => [ 'context' => 'custom-folders' ],
			],
			'expected' => [
				'allowed' => true,
				'checked' => [ 'wp:manage', 'custom-folders:bulk-optimize' ],
			],
		],
		'shouldDenyWhenUserCannotManage'                     => [
			'config'   => [
				'allowed' => [ 'wp:bulk-optimize' ],
				'input'   => [ 'context' => 'wp' ],
			],
			'expected' => [
				'allowed' => false,
				'checked' => [ 'wp:manage' ],
			],
		],
		'shouldDenyCustomFoldersWithoutTheirCapability'      => [
			// Allowed on the media library, but not on custom folders (e.g. a subsite admin).
			'config'   => [
				'allowed' => [ 'wp:manage', 'wp:bulk-optimize' ],
				'input'   => [ 'context' => 'custom-folders' ],
			],
			'expected' => [
				'allowed' => false,
				'checked' => [ 'wp:manage', 'custom-folders:bulk-optimize' ],
			],
		],
		'shouldCheckWpContextWhenNoInputIsGiven'             => [
			'config'   => [
				'allowed' => [ 'wp:manage', 'wp:bulk-optimize' ],
				'input'   => null,
			],
			'expected' => [
				'allowed' => true,
				'checked' => [ 'wp:manage', 'wp:bulk-optimize' ],
			],
		],
		'shouldCheckWpContextForAnUnknownContext'            => [
			'config'   => [
				'allowed' => [ 'wp:manage', 'wp:bulk-optimize' ],
				'input'   => [ 'context' => 'unknown' ],
			],
			'expected' => [
				'allowed' => true,
				'checked' => [ 'wp:manage', 'wp:bulk-optimize' ],
			],
		],
	],
];

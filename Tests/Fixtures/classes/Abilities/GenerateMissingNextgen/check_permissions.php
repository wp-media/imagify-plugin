<?php

return [
	'test_data' => [
		'shouldAllowWhenUserCanBulkOptimizeEveryContext'      => [
			'config'   => [
				'contexts' => [ 'wp', 'custom-folders' ],
				'allowed'  => [ 'wp:manage', 'wp:bulk-optimize', 'custom-folders:bulk-optimize' ],
			],
			'expected' => true,
		],
		'shouldDenyWhenUserCannotManage'                      => [
			'config'   => [
				'contexts' => [ 'wp' ],
				'allowed'  => [ 'wp:bulk-optimize' ],
			],
			'expected' => false,
		],
		'shouldDenyWhenUserCannotBulkOptimizeCustomFolders'   => [
			// Allowed on the media library, but not on custom folders (e.g. a subsite admin).
			'config'   => [
				'contexts' => [ 'wp', 'custom-folders' ],
				'allowed'  => [ 'wp:manage', 'wp:bulk-optimize' ],
			],
			'expected' => false,
		],
		'shouldAllowWhenCustomFoldersAreNotPartOfTheContexts' => [
			'config'   => [
				'contexts' => [ 'wp' ],
				'allowed'  => [ 'wp:manage', 'wp:bulk-optimize' ],
			],
			'expected' => true,
		],
	],
];

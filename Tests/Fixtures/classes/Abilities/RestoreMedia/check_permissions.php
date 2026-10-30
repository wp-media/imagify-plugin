<?php

return [
	'test_data' => [
		'shouldDenyWhenUserCannotManage'                  => [
			'config'   => [
				'media_context' => 'wp',
				'input'         => [ 'media_id' => 12 ],
				'allowed'       => [ 'wp:manual-restore' ],
			],
			'expected' => false,
		],
		'shouldAllowWhenUserCanRestoreLibraryMedia'       => [
			'config'   => [
				'media_context' => 'wp',
				'input'         => [ 'media_id' => 12 ],
				'allowed'       => [ 'wp:manage', 'wp:manual-restore' ],
			],
			'expected' => true,
		],
		'shouldAllowWhenUserCanRestoreCustomFolderFile'   => [
			'config'   => [
				'media_context' => 'custom-folders',
				'input'         => [ 'media_id' => 12 ],
				'allowed'       => [ 'wp:manage', 'custom-folders:manual-restore' ],
			],
			'expected' => true,
		],
		'shouldDenyCustomFolderFileWithoutTheirCapability' => [
			// Allowed on the media library, but not on custom folders (e.g. a subsite admin).
			'config'   => [
				'media_context' => 'custom-folders',
				'input'         => [ 'media_id' => 12 ],
				'allowed'       => [ 'wp:manage', 'wp:manual-restore' ],
			],
			'expected' => false,
		],
		'shouldAllowManagerWhenMediaDoesNotResolve'       => [
			// Nothing to restore: execute() returns its actionable error.
			'config'   => [
				'media_context' => null,
				'input'         => [ 'media_id' => 12 ],
				'allowed'       => [ 'wp:manage' ],
			],
			'expected' => true,
		],
		'shouldAllowManagerWhenNoIdentifierIsGiven'       => [
			'config'   => [
				'media_context' => 'custom-folders',
				'input'         => null,
				'allowed'       => [ 'wp:manage' ],
			],
			'expected' => true,
		],
	],
];

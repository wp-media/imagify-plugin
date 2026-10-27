<?php

return [
	'test_data' => [
		'shouldDenySiteAdminCustomFoldersOnMultisite'      => [
			'config'   => [
				'site'                         => 'multisite',
				'super_admin'                  => false,
				'deny_custom_folders_capacity' => false,
				'args'                         => [ 'context' => 'custom-folders' ],
			],
			'expected' => [ 'is_denied' => true ],
		],
		'shouldAllowSiteAdminMediaLibraryOnMultisite'      => [
			'config'   => [
				'site'                         => 'multisite',
				'super_admin'                  => false,
				'deny_custom_folders_capacity' => false,
				'args'                         => [ 'context' => 'wp' ],
			],
			'expected' => [ 'is_denied' => false ],
		],
		'shouldAllowSuperAdminCustomFoldersOnMultisite'    => [
			'config'   => [
				'site'                         => 'multisite',
				'super_admin'                  => true,
				'deny_custom_folders_capacity' => false,
				'args'                         => [ 'context' => 'custom-folders' ],
			],
			'expected' => [ 'is_denied' => false ],
		],
		'shouldAllowAdminCustomFoldersOnSingleSite'        => [
			'config'   => [
				'site'                         => 'single',
				'super_admin'                  => false,
				'deny_custom_folders_capacity' => false,
				'args'                         => [ 'context' => 'custom-folders' ],
			],
			'expected' => [ 'is_denied' => false ],
		],
		'shouldHonorCapacityFilterOnCustomFolders'         => [
			'config'   => [
				'site'                         => 'any',
				'super_admin'                  => true,
				'deny_custom_folders_capacity' => true,
				'args'                         => [ 'context' => 'custom-folders' ],
			],
			'expected' => [ 'is_denied' => true ],
		],
		'shouldKeepMediaLibraryWhenFilterDeniesCustomFolders' => [
			'config'   => [
				'site'                         => 'any',
				'super_admin'                  => true,
				'deny_custom_folders_capacity' => true,
				'args'                         => [ 'context' => 'wp' ],
			],
			'expected' => [ 'is_denied' => false ],
		],
		'shouldDenySiteAdminMediaLibraryWhenNetworkActive' => [
			'config'   => [
				'site'                         => 'network-active',
				'super_admin'                  => false,
				'deny_custom_folders_capacity' => false,
				'args'                         => [ 'context' => 'wp' ],
			],
			'expected' => [ 'is_denied' => true ],
		],
		'shouldAllowSuperAdminMediaLibraryWhenNetworkActive' => [
			'config'   => [
				'site'                         => 'network-active',
				'super_admin'                  => true,
				'deny_custom_folders_capacity' => false,
				'args'                         => [ 'context' => 'wp' ],
			],
			'expected' => [ 'is_denied' => false ],
		],
		'shouldDenySiteAdminCustomFoldersWhenNetworkActive' => [
			'config'   => [
				'site'                         => 'network-active',
				'super_admin'                  => false,
				'deny_custom_folders_capacity' => false,
				'args'                         => [ 'context' => 'custom-folders' ],
			],
			'expected' => [ 'is_denied' => true ],
		],
		'shouldAllowSuperAdminCustomFoldersWhenNetworkActive' => [
			'config'   => [
				'site'                         => 'network-active',
				'super_admin'                  => true,
				'deny_custom_folders_capacity' => false,
				'args'                         => [ 'context' => 'custom-folders' ],
			],
			'expected' => [ 'is_denied' => false ],
		],
	],
];

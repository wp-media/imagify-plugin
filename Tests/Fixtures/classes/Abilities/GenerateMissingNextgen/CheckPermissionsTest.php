<?php

return [
	'test_data' => [
		'shouldDenySiteAdminOnMultisite'         => [
			'config'   => [
				'site'                         => 'multisite',
				'super_admin'                  => false,
				'deny_custom_folders_capacity' => false,
				'custom_folders'               => true,
			],
			'expected' => [ 'is_denied' => true ],
		],
		'shouldAllowSuperAdminOnMultisite'       => [
			'config'   => [
				'site'                         => 'multisite',
				'super_admin'                  => true,
				'deny_custom_folders_capacity' => false,
				'custom_folders'               => true,
			],
			'expected' => [ 'is_denied' => false ],
		],
		'shouldAllowAdminOnSingleSite'           => [
			'config'   => [
				'site'                         => 'single',
				'super_admin'                  => false,
				'deny_custom_folders_capacity' => false,
				'custom_folders'               => true,
			],
			'expected' => [ 'is_denied' => false ],
		],
		'shouldHonorImagifyCapacityFilter'       => [
			'config'   => [
				'site'                         => 'any',
				'super_admin'                  => true,
				'deny_custom_folders_capacity' => true,
				'custom_folders'               => true,
			],
			'expected' => [ 'is_denied' => true ],
		],
		'shouldDenySiteAdminWhenNetworkActive'   => [
			'config'   => [
				'site'                         => 'network-active',
				'super_admin'                  => false,
				'deny_custom_folders_capacity' => false,
				'custom_folders'               => true,
			],
			'expected' => [ 'is_denied' => true ],
		],
		'shouldAllowSuperAdminWhenNetworkActive' => [
			'config'   => [
				'site'                         => 'network-active',
				'super_admin'                  => true,
				'deny_custom_folders_capacity' => false,
				'custom_folders'               => true,
			],
			'expected' => [ 'is_denied' => false ],
		],
		'shouldAllowSiteAdminMediaLibraryOnlyOnMultisite'       => [
			'config'   => [
				'site'                         => 'multisite',
				'super_admin'                  => false,
				'deny_custom_folders_capacity' => false,
				'custom_folders'               => false,
			],
			'expected' => [ 'is_denied' => false ],
		],
		'shouldAllowSiteAdminMediaLibraryOnlyWhenNetworkActive' => [
			'config'   => [
				'site'                         => 'network-active',
				'super_admin'                  => false,
				'deny_custom_folders_capacity' => false,
				'custom_folders'               => false,
			],
			'expected' => [ 'is_denied' => false ],
		],
	],
];

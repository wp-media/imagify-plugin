<?php

return [
	'test_data' => [
		'shouldGrantSiteAdminOnSingleSite' => [
			'config'   => [
				'site'        => 'single',
				'role'        => 'administrator',
				'super_admin' => false,
				'context'     => 'wp',
				'filter'      => 'none',
			],
			'expected' => [
				'capacity'     => 'manage_options',
				'current_user' => true,
				'other_user'   => true,
			],
		],
		'shouldGrantSiteAdminWhenSiteActivated' => [
			'config'   => [
				'site'        => 'multisite',
				'role'        => 'administrator',
				'super_admin' => false,
				'context'     => 'wp',
				'filter'      => 'none',
			],
			'expected' => [
				'capacity'     => 'manage_options',
				'current_user' => true,
				'other_user'   => true,
			],
		],
		'shouldGrantSiteAdminWhenNetworkActive' => [
			'config'   => [
				'site'        => 'network-active',
				'role'        => 'administrator',
				'super_admin' => false,
				'context'     => 'wp',
				'filter'      => 'none',
			],
			'expected' => [
				'capacity'     => 'manage_options',
				'current_user' => true,
				'other_user'   => true,
			],
		],
		'shouldGrantSuperAdminWhenNetworkActive' => [
			'config'   => [
				'site'        => 'network-active',
				'role'        => 'administrator',
				'super_admin' => true,
				'context'     => 'wp',
				'filter'      => 'none',
			],
			'expected' => [
				'capacity'     => 'manage_options',
				'current_user' => true,
				'other_user'   => true,
			],
		],
		'shouldDenySubscriberWhenNetworkActive' => [
			'config'   => [
				'site'        => 'network-active',
				'role'        => 'subscriber',
				'super_admin' => false,
				'context'     => 'wp',
				'filter'      => 'none',
			],
			'expected' => [
				'capacity'     => 'manage_options',
				'current_user' => false,
				'other_user'   => false,
			],
		],
		'shouldNeverGrantSiteAdminOnCustomFoldersWhenNetworkActive' => [
			'config'   => [
				'site'        => 'network-active',
				'role'        => 'administrator',
				'super_admin' => false,
				'context'     => 'custom-folders',
				'filter'      => 'none',
			],
			'expected' => [
				'capacity'     => 'manage_network_options',
				'current_user' => false,
				'other_user'   => false,
			],
		],
		'shouldFollowManageOnCustomFoldersWhenSiteActivated' => [
			'config'   => [
				'site'        => 'multisite',
				'role'        => 'administrator',
				'super_admin' => false,
				'context'     => 'custom-folders',
				'filter'      => 'none',
			],
			'expected' => [
				'capacity'     => 'manage_options',
				'current_user' => true,
				'other_user'   => true,
			],
		],
		'shouldGrantSuperAdminOnCustomFoldersWhenNetworkActive' => [
			'config'   => [
				'site'        => 'network-active',
				'role'        => 'administrator',
				'super_admin' => true,
				'context'     => 'custom-folders',
				'filter'      => 'none',
			],
			'expected' => [
				'capacity'     => 'manage_network_options',
				'current_user' => true,
				'other_user'   => true,
			],
		],
		'shouldGrantAdminOnCustomFoldersOnSingleSite' => [
			'config'   => [
				'site'        => 'single',
				'role'        => 'administrator',
				'super_admin' => false,
				'context'     => 'custom-folders',
				'filter'      => 'none',
			],
			'expected' => [
				'capacity'     => 'manage_options',
				'current_user' => true,
				'other_user'   => true,
			],
		],
		'shouldDenyWhenImagifyCapacityDeniesManageSite' => [
			'config'   => [
				'site'        => 'network-active',
				'role'        => 'administrator',
				'super_admin' => false,
				'context'     => 'wp',
				'filter'      => 'capacity-denies-manage-site',
			],
			'expected' => [
				'capacity'     => 'do_not_allow',
				'current_user' => false,
				'other_user'   => false,
			],
		],
		'shouldDenySiteAdminWithDocumentedOptOutWhenNetworkActive' => [
			'config'   => [
				'site'        => 'network-active',
				'role'        => 'administrator',
				'super_admin' => false,
				'context'     => 'wp',
				'filter'      => 'capacity-keeps-manage-site-network-only',
			],
			'expected' => [
				'capacity'     => 'manage_network_options',
				'current_user' => false,
				'other_user'   => false,
			],
		],
		'shouldGrantSuperAdminWithDocumentedOptOutWhenNetworkActive' => [
			'config'   => [
				'site'        => 'network-active',
				'role'        => 'administrator',
				'super_admin' => true,
				'context'     => 'wp',
				'filter'      => 'capacity-keeps-manage-site-network-only',
			],
			'expected' => [
				'capacity'     => 'manage_network_options',
				'current_user' => true,
				'other_user'   => true,
			],
		],
		'shouldStillGrantSiteAdminWhenManageIsSetToItsDefault' => [
			'config'   => [
				'site'        => 'network-active',
				'role'        => 'administrator',
				'super_admin' => false,
				'context'     => 'wp',
				'filter'      => 'capacity-sets-manage-to-default',
			],
			'expected' => [
				'capacity'     => 'manage_options',
				'current_user' => true,
				'other_user'   => true,
			],
		],
		'shouldFollowCustomizedManageCapacity' => [
			'config'   => [
				'site'        => 'network-active',
				'role'        => 'administrator',
				'super_admin' => false,
				'context'     => 'wp',
				'filter'      => 'capacity-customizes-manage',
			],
			'expected' => [
				'capacity'     => 'manage_network_users',
				'current_user' => false,
				'other_user'   => false,
			],
		],
		'shouldDenyWhenCurrentUserCanFilterDenies' => [
			'config'   => [
				'site'        => 'network-active',
				'role'        => 'administrator',
				'super_admin' => false,
				'context'     => 'wp',
				'filter'      => 'current-user-can-denies-manage-site',
			],
			'expected' => [
				'current_user' => false,
				'other_user'   => true,
			],
		],
		'shouldDenyWhenUserCanFilterDenies' => [
			'config'   => [
				'site'        => 'network-active',
				'role'        => 'administrator',
				'super_admin' => false,
				'context'     => 'wp',
				'filter'      => 'user-can-denies-manage-site',
			],
			'expected' => [
				'current_user' => false,
				'other_user'   => false,
			],
		],
		'shouldDenyWhenCurrentUserCanFilterDeniesManage' => [
			'config'   => [
				'site'        => 'network-active',
				'role'        => 'administrator',
				'super_admin' => false,
				'context'     => 'wp',
				'filter'      => 'current-user-can-denies-manage',
			],
			'expected' => [
				'current_user' => false,
				'other_user'   => true,
			],
		],
		'shouldDenyWhenUserCanFilterDeniesManage' => [
			'config'   => [
				'site'        => 'network-active',
				'role'        => 'administrator',
				'super_admin' => false,
				'context'     => 'wp',
				'filter'      => 'user-can-denies-manage',
			],
			'expected' => [
				'current_user' => false,
				'other_user'   => false,
			],
		],
		'shouldDenyWhenCurrentUserCanFilterDeniesManageOnSingleSite' => [
			'config'   => [
				'site'        => 'single',
				'role'        => 'administrator',
				'super_admin' => false,
				'context'     => 'wp',
				'filter'      => 'current-user-can-denies-manage',
			],
			'expected' => [
				'current_user' => false,
				'other_user'   => true,
			],
		],
		'shouldNeverGrantSubscriberWhenFiltersGrantManage' => [
			'config'   => [
				'site'        => 'network-active',
				'role'        => 'subscriber',
				'super_admin' => false,
				'context'     => 'wp',
				'filter'      => 'filters-grant-manage',
			],
			'expected' => [
				'current_user' => false,
				'other_user'   => false,
			],
		],
	],
];

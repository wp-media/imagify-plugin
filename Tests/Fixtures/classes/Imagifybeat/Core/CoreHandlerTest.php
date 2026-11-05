<?php
/**
 * What each user gets from Imagifybeat, per type of site.
 *
 * Each listener only answers users who can open the screen that polls it:
 * - Media Library / media modal: the media must be editable by the user.
 * - Bulk Optimization: 'bulk-optimize' on the context (the "Other Media" group is network-wide on multisite).
 * - Settings: 'manage' (a network setting when Imagify is network-activated).
 * - Other Media: 'optimize' / 'bulk-optimize' on the custom-folders context (super admins only on multisite).
 *
 * On multisite, users are tested on a sub-site.
 */

$all = [
	'requirements',
	'bulk_stats_library',
	'bulk_stats_custom_folders',
	'bulk_status_library',
	'bulk_status_custom_folders',
	'options_status',
	'library_status',
	'custom_folders_status',
];

$author = [
	'can_use' => true,
	'allowed' => [ 'library_status' ],
];

$subscriber = [
	'can_use' => false,
	'allowed' => [],
];

return [
	'test_data' => [
		// Single site.
		'shouldReturnEverythingToAdminOnSingleSite'        => [
			'config'   => [
				'site' => 'single',
				'role' => 'administrator',
			],
			'expected' => [
				'can_use' => true,
				'allowed' => $all,
			],
		],
		'shouldReturnOwnLibraryStatusToAuthorOnSingleSite' => [
			'config'   => [
				'site' => 'single',
				'role' => 'author',
			],
			'expected' => $author,
		],
		'shouldReturnNothingToSubscriberOnSingleSite'      => [
			'config'   => [
				'site' => 'single',
				'role' => 'subscriber',
			],
			'expected' => $subscriber,
		],

		// Multisite, Imagify activated per site.
		'shouldReturnSiteDataToSiteAdminOnMultisite'       => [
			'config'   => [
				'site' => 'multisite',
				'role' => 'administrator',
			],
			'expected' => [
				'can_use' => true,
				'allowed' => [
					'requirements',
					'bulk_stats_library',
					'bulk_status_library',
					'options_status',
					'library_status',
				],
			],
		],
		'shouldReturnEverythingToSuperAdminOnMultisite'    => [
			'config'   => [
				'site' => 'multisite',
				'role' => 'super_admin',
			],
			'expected' => [
				'can_use' => true,
				'allowed' => $all,
			],
		],
		'shouldReturnOwnLibraryStatusToAuthorOnMultisite'  => [
			'config'   => [
				'site' => 'multisite',
				'role' => 'author',
			],
			'expected' => $author,
		],
		'shouldReturnNothingToSubscriberOnMultisite'       => [
			'config'   => [
				'site' => 'multisite',
				'role' => 'subscriber',
			],
			'expected' => $subscriber,
		],

		// Multisite, Imagify network-activated: the settings page lives in the network admin.
		'shouldReturnSiteDataToSiteAdminWhenNetworkActive' => [
			'config'   => [
				'site' => 'network-active',
				'role' => 'administrator',
			],
			'expected' => [
				'can_use' => true,
				'allowed' => [
					'requirements',
					'bulk_stats_library',
					'bulk_status_library',
					'library_status',
				],
			],
		],
		'shouldReturnEverythingToSuperAdminWhenNetworkActive' => [
			'config'   => [
				'site' => 'network-active',
				'role' => 'super_admin',
			],
			'expected' => [
				'can_use' => true,
				'allowed' => $all,
			],
		],
		'shouldReturnOwnLibraryStatusToAuthorWhenNetworkActive' => [
			'config'   => [
				'site' => 'network-active',
				'role' => 'author',
			],
			'expected' => $author,
		],
		'shouldReturnNothingToSubscriberWhenNetworkActive' => [
			'config'   => [
				'site' => 'network-active',
				'role' => 'subscriber',
			],
			'expected' => $subscriber,
		],
	],
];

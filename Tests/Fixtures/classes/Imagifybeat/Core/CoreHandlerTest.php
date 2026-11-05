<?php
/**
 * What each user gets from Imagifybeat, per type of site. On multisite, the requests are made on a sub-site.
 *
 * Each listener only answers users who can open the screen that polls it:
 * - Media Library / media modal: the media must be editable by the user (the test media belongs to the user, so
 *   contributors can edit it too).
 * - Bulk Optimization: 'bulk-optimize' on the context (the "Other Media" group is network-wide on multisite).
 * - Settings: 'manage' (a network setting when Imagify is network-activated).
 * - Other Media: 'optimize' / 'bulk-optimize' on the custom-folders context (super admins only on multisite).
 *
 * 'status': 'rejected' (403, no nonce), 'nonce_refreshed' (expired nonce: a fresh one, no data) or 'answered'.
 * 'allowed': the probes that return data, when answered.
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

return [
	'test_data' => [
		'shouldAnswerEverythingToAdminOnSingleSite'        => [
			'config'   => [
				'site'  => 'single',
				'role'  => 'administrator',
				'nonce' => 'valid',
			],
			'expected' => [
				'status'  => 'answered',
				'allowed' => $all,
			],
		],
		'shouldAnswerOwnLibraryStatusToAuthorOnSingleSite' => [
			'config'   => [
				'site'  => 'single',
				'role'  => 'author',
				'nonce' => 'valid',
			],
			'expected' => [
				'status'  => 'answered',
				'allowed' => [
					'library_status',
				],
			],
		],
		'shouldAnswerOwnLibraryStatusToContributorOnSingleSite' => [
			'config'   => [
				'site'  => 'single',
				'role'  => 'contributor',
				'nonce' => 'valid',
			],
			'expected' => [
				'status'  => 'answered',
				'allowed' => [
					'library_status',
				],
			],
		],
		'shouldRejectSubscriberOnSingleSite'               => [
			'config'   => [
				'site'  => 'single',
				'role'  => 'subscriber',
				'nonce' => 'valid',
			],
			'expected' => [
				'status'  => 'rejected',
				'allowed' => [],
			],
		],
		'shouldRejectSubscriberWithInvalidNonceOnSingleSite' => [
			'config'   => [
				'site'  => 'single',
				'role'  => 'subscriber',
				'nonce' => 'invalid',
			],
			'expected' => [
				'status'  => 'rejected',
				'allowed' => [],
			],
		],
		'shouldRefreshNonceForAdminOnSingleSite'           => [
			'config'   => [
				'site'  => 'single',
				'role'  => 'administrator',
				'nonce' => 'invalid',
			],
			'expected' => [
				'status'  => 'nonce_refreshed',
				'allowed' => [],
			],
		],
		'shouldRefreshNonceForAuthorOnSingleSite'          => [
			'config'   => [
				'site'  => 'single',
				'role'  => 'author',
				'nonce' => 'invalid',
			],
			'expected' => [
				'status'  => 'nonce_refreshed',
				'allowed' => [],
			],
		],
		'shouldAnswerSiteDataToSiteAdminOnMultisite'       => [
			'config'   => [
				'site'  => 'multisite',
				'role'  => 'administrator',
				'nonce' => 'valid',
			],
			'expected' => [
				'status'  => 'answered',
				'allowed' => [
					'requirements',
					'bulk_stats_library',
					'bulk_status_library',
					'options_status',
					'library_status',
				],
			],
		],
		'shouldAnswerEverythingToSuperAdminOnMultisite'    => [
			'config'   => [
				'site'  => 'multisite',
				'role'  => 'super_admin',
				'nonce' => 'valid',
			],
			'expected' => [
				'status'  => 'answered',
				'allowed' => $all,
			],
		],
		'shouldAnswerOwnLibraryStatusToAuthorOnMultisite'  => [
			'config'   => [
				'site'  => 'multisite',
				'role'  => 'author',
				'nonce' => 'valid',
			],
			'expected' => [
				'status'  => 'answered',
				'allowed' => [
					'library_status',
				],
			],
		],
		'shouldAnswerOwnLibraryStatusToContributorOnMultisite' => [
			'config'   => [
				'site'  => 'multisite',
				'role'  => 'contributor',
				'nonce' => 'valid',
			],
			'expected' => [
				'status'  => 'answered',
				'allowed' => [
					'library_status',
				],
			],
		],
		'shouldRejectSubscriberOnMultisite'                => [
			'config'   => [
				'site'  => 'multisite',
				'role'  => 'subscriber',
				'nonce' => 'valid',
			],
			'expected' => [
				'status'  => 'rejected',
				'allowed' => [],
			],
		],
		'shouldRejectSubscriberWithInvalidNonceOnMultisite' => [
			'config'   => [
				'site'  => 'multisite',
				'role'  => 'subscriber',
				'nonce' => 'invalid',
			],
			'expected' => [
				'status'  => 'rejected',
				'allowed' => [],
			],
		],
		'shouldRefreshNonceForAdminOnMultisite'            => [
			'config'   => [
				'site'  => 'multisite',
				'role'  => 'administrator',
				'nonce' => 'invalid',
			],
			'expected' => [
				'status'  => 'nonce_refreshed',
				'allowed' => [],
			],
		],
		'shouldRefreshNonceForAuthorOnMultisite'           => [
			'config'   => [
				'site'  => 'multisite',
				'role'  => 'author',
				'nonce' => 'invalid',
			],
			'expected' => [
				'status'  => 'nonce_refreshed',
				'allowed' => [],
			],
		],
		'shouldAnswerSiteDataToSiteAdminWhenNetworkActive' => [
			'config'   => [
				'site'  => 'network-active',
				'role'  => 'administrator',
				'nonce' => 'valid',
			],
			'expected' => [
				'status'  => 'answered',
				'allowed' => [
					'requirements',
					'bulk_stats_library',
					'bulk_status_library',
					'library_status',
				],
			],
		],
		'shouldAnswerEverythingToSuperAdminWhenNetworkActive' => [
			'config'   => [
				'site'  => 'network-active',
				'role'  => 'super_admin',
				'nonce' => 'valid',
			],
			'expected' => [
				'status'  => 'answered',
				'allowed' => $all,
			],
		],
		'shouldAnswerOwnLibraryStatusToAuthorWhenNetworkActive' => [
			'config'   => [
				'site'  => 'network-active',
				'role'  => 'author',
				'nonce' => 'valid',
			],
			'expected' => [
				'status'  => 'answered',
				'allowed' => [
					'library_status',
				],
			],
		],
		'shouldAnswerOwnLibraryStatusToContributorWhenNetworkActive' => [
			'config'   => [
				'site'  => 'network-active',
				'role'  => 'contributor',
				'nonce' => 'valid',
			],
			'expected' => [
				'status'  => 'answered',
				'allowed' => [
					'library_status',
				],
			],
		],
		'shouldRejectSubscriberWhenNetworkActive'          => [
			'config'   => [
				'site'  => 'network-active',
				'role'  => 'subscriber',
				'nonce' => 'valid',
			],
			'expected' => [
				'status'  => 'rejected',
				'allowed' => [],
			],
		],
		'shouldRejectSubscriberWithInvalidNonceWhenNetworkActive' => [
			'config'   => [
				'site'  => 'network-active',
				'role'  => 'subscriber',
				'nonce' => 'invalid',
			],
			'expected' => [
				'status'  => 'rejected',
				'allowed' => [],
			],
		],
		'shouldRefreshNonceForAdminWhenNetworkActive'      => [
			'config'   => [
				'site'  => 'network-active',
				'role'  => 'administrator',
				'nonce' => 'invalid',
			],
			'expected' => [
				'status'  => 'nonce_refreshed',
				'allowed' => [],
			],
		],
		'shouldRefreshNonceForAuthorWhenNetworkActive'     => [
			'config'   => [
				'site'  => 'network-active',
				'role'  => 'author',
				'nonce' => 'invalid',
			],
			'expected' => [
				'status'  => 'nonce_refreshed',
				'allowed' => [],
			],
		],
	],
];

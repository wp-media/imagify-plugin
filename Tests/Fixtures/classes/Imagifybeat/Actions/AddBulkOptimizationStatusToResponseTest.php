<?php
/**
 * The bulk status only uses valid contexts the user can bulk-optimize: the Media Library for site administrators, the
 * "Other Media" group too for super admins (or administrators on a single site).
 * 'expected' lists the contexts returned, null when nothing is returned.
 */

$library        = [
	'groupID' => 'library',
	'context' => 'wp',
];
$custom_folders = [
	'groupID' => 'custom-folders',
	'context' => 'custom-folders',
];

return [
	'test_data' => [
		'shouldReturnBothGroupsToAdminOnSingleSite'       => [
			'config'   => [
				'site'   => 'single',
				'role'   => 'administrator',
				'groups' => [ $library, $custom_folders ],
			],
			'expected' => [ 'wp', 'custom-folders' ],
		],
		'shouldReturnLibraryToSiteAdminOnMultisite'       => [
			'config'   => [
				'site'   => 'multisite',
				'role'   => 'administrator',
				'groups' => [ $library, $custom_folders ],
			],
			'expected' => [ 'wp' ],
		],
		'shouldReturnLibraryToSiteAdminWhenNetworkActive' => [
			'config'   => [
				'site'   => 'network-active',
				'role'   => 'administrator',
				'groups' => [ $library, $custom_folders ],
			],
			'expected' => [ 'wp' ],
		],
		'shouldReturnBothGroupsToSuperAdmin'              => [
			'config'   => [
				'site'   => 'any',
				'role'   => 'super_admin',
				'groups' => [ $library, $custom_folders ],
			],
			'expected' => [ 'wp', 'custom-folders' ],
		],
		'shouldOnlyUseValidContexts'                      => [
			'config'   => [
				'site'   => 'any',
				'role'   => 'super_admin',
				'groups' => [
					'not-a-group',
					[ 'groupID' => 'library' ],
					[ 'context' => [ 'wp' ] ],
					[ 'context' => '' ],
					[ 'context' => 'bogus' ],
					[ 'context' => '../../wp-config' ],
					$library,
				],
			],
			'expected' => [ 'wp' ],
		],
		'shouldReturnNothingForInvalidContextsOnly'       => [
			'config'   => [
				'site'   => 'any',
				'role'   => 'super_admin',
				'groups' => [
					[ 'context' => [ 'wp' ] ],
					[ 'context' => 'bogus' ],
				],
			],
			'expected' => null,
		],
		'shouldReturnNothingToAuthor'                     => [
			'config'   => [
				'site'   => 'any',
				'role'   => 'author',
				'groups' => [ $library, $custom_folders ],
			],
			'expected' => null,
		],
	],
];

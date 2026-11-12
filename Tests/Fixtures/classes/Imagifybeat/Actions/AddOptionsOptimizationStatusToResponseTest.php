<?php
/**
 * The next-gen generation progress (settings page) is returned to users who can manage Imagify: site administrators,
 * except when Imagify is network-activated (the settings page is in the network admin then).
 * 'expected' is the total returned, null when nothing is returned.
 */

return [
	'test_data' => [
		'shouldReturnToAdminOnSingleSite'             => [
			'config'   => [
				'site'     => 'single',
				'role'     => 'administrator',
				'contexts' => [ 'wp' ],
			],
			'expected' => 5,
		],
		'shouldReturnToSiteAdminOnMultisite'          => [
			'config'   => [
				'site'     => 'multisite',
				'role'     => 'administrator',
				'contexts' => [ 'wp' ],
			],
			'expected' => 5,
		],
		'shouldNotReturnToSiteAdminWhenNetworkActive' => [
			'config'   => [
				'site'     => 'network-active',
				'role'     => 'administrator',
				'contexts' => [ 'wp' ],
			],
			'expected' => null,
		],
		'shouldReturnToSuperAdmin'                    => [
			'config'   => [
				'site'     => 'any',
				'role'     => 'super_admin',
				'contexts' => [ 'wp' ],
			],
			'expected' => 5,
		],
		'shouldIgnoreInvalidContexts'                 => [
			'config'   => [
				'site'     => 'any',
				'role'     => 'super_admin',
				'contexts' => [ 'wp', [ 'wp' ], '', 'bogus' ],
			],
			'expected' => 5,
		],
		'shouldNotReturnToEditor'                     => [
			'config'   => [
				'site'     => 'any',
				'role'     => 'editor',
				'contexts' => [ 'wp' ],
			],
			'expected' => null,
		],
	],
];

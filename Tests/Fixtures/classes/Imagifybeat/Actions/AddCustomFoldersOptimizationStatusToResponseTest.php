<?php
/**
 * "Other Media" file data is only returned to users of the "Other Media" page (super admins only on multisite), and
 * only for the custom-folders context.
 */

return [
	'test_data' => [
		'shouldReturnToAdminOnSingleSite'             => [
			'config'   => [
				'site'    => 'single',
				'role'    => 'administrator',
				'context' => 'custom-folders',
			],
			'expected' => true,
		],
		'shouldNotReturnToSiteAdminOnMultisite'       => [
			'config'   => [
				'site'    => 'multisite',
				'role'    => 'administrator',
				'context' => 'custom-folders',
			],
			'expected' => false,
		],
		'shouldNotReturnToSiteAdminWhenNetworkActive' => [
			'config'   => [
				'site'    => 'network-active',
				'role'    => 'administrator',
				'context' => 'custom-folders',
			],
			'expected' => false,
		],
		'shouldReturnToSuperAdmin'                    => [
			'config'   => [
				'site'    => 'any',
				'role'    => 'super_admin',
				'context' => 'custom-folders',
			],
			'expected' => true,
		],
		'shouldIgnoreOtherContexts'                   => [
			'config'   => [
				'site'    => 'any',
				'role'    => 'super_admin',
				'context' => 'wp',
			],
			'expected' => false,
		],
		'shouldNotReturnToEditor'                     => [
			'config'   => [
				'site'    => 'any',
				'role'    => 'editor',
				'context' => 'custom-folders',
			],
			'expected' => false,
		],
		'shouldNotReturnToAuthor'                     => [
			'config'   => [
				'site'    => 'any',
				'role'    => 'author',
				'context' => 'custom-folders',
			],
			'expected' => false,
		],
	],
];

<?php

return [
	'test_data' => [
		'shouldAllowAdminOnSingleSite' => [
			'config'   => [
				'site'        => 'single',
				'super_admin' => false,
			],
			'expected' => [ 'is_denied' => false ],
		],
		'shouldAllowSiteAdminWhenSiteActivated' => [
			'config'   => [
				'site'        => 'multisite',
				'super_admin' => false,
			],
			'expected' => [ 'is_denied' => false ],
		],
		'shouldAllowSuperAdminWhenSiteActivated' => [
			'config'   => [
				'site'        => 'multisite',
				'super_admin' => true,
			],
			'expected' => [ 'is_denied' => false ],
		],
		'shouldDenySiteAdminWhenNetworkActive' => [
			'config'   => [
				'site'        => 'network-active',
				'super_admin' => false,
			],
			'expected' => [ 'is_denied' => true ],
		],
		'shouldAllowSuperAdminWhenNetworkActive' => [
			'config'   => [
				'site'        => 'network-active',
				'super_admin' => true,
			],
			'expected' => [ 'is_denied' => false ],
		],
	],
];

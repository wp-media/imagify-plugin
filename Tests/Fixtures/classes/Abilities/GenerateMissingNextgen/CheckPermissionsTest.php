<?php

return [
	'test_data' => [
		'shouldDenySiteAdminOnMultisite'     => [
			'config'   => [
				'site'                         => 'multisite',
				'super_admin'                  => false,
				'deny_custom_folders_capacity' => false,
			],
			'expected' => [ 'is_denied' => true ],
		],
		'shouldAllowSuperAdminOnMultisite'   => [
			'config'   => [
				'site'                         => 'multisite',
				'super_admin'                  => true,
				'deny_custom_folders_capacity' => false,
			],
			'expected' => [ 'is_denied' => false ],
		],
		'shouldAllowAdminOnSingleSite'       => [
			'config'   => [
				'site'                         => 'single',
				'super_admin'                  => false,
				'deny_custom_folders_capacity' => false,
			],
			'expected' => [ 'is_denied' => false ],
		],
		'shouldHonorImagifyCapacityFilter'   => [
			'config'   => [
				'site'                         => 'any',
				'super_admin'                  => true,
				'deny_custom_folders_capacity' => true,
			],
			'expected' => [ 'is_denied' => true ],
		],
	],
];

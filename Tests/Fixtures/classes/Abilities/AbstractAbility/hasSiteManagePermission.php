<?php

return [
	'test_data' => [
		'shouldAllowManager'            => [
			'config'   => [ 'allowed' => [ 'manage', 'manage-site' ] ],
			'expected' => [
				'site_permission' => true,
				'site_only'       => false,
			],
		],
		'shouldAllowManagerWithoutSite' => [
			'config'   => [ 'allowed' => [ 'manage' ] ],
			'expected' => [
				'site_permission' => true,
				'site_only'       => false,
			],
		],
		'shouldAllowSiteOnlyCaller'     => [
			'config'   => [ 'allowed' => [ 'manage-site' ] ],
			'expected' => [
				'site_permission' => true,
				'site_only'       => true,
			],
		],
		'shouldDenyOtherUsers'          => [
			'config'   => [ 'allowed' => [] ],
			'expected' => [
				'site_permission' => false,
				'site_only'       => false,
			],
		],
	],
];

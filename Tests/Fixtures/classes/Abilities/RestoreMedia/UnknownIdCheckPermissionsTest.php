<?php

return [
	'test_data' => [
		'shouldReportInvalidMediaForAdminOnSingle' => [
			'config'   => [
				'site'        => 'single',
				'super_admin' => false,
				'target'      => 'unknown',
			],
			'expected' => [ 'message' => 'Invalid media.' ],
		],
		'shouldReportNotOptimizedForAdminAttachmentOnSingle' => [
			'config'   => [
				'site'        => 'single',
				'super_admin' => false,
				'target'      => 'attachment',
			],
			'expected' => [ 'message' => 'not optimized' ],
		],
		'shouldReportInvalidMediaForAdminOnMultisite' => [
			'config'   => [
				'site'        => 'multisite',
				'super_admin' => false,
				'target'      => 'unknown',
			],
			'expected' => [ 'message' => 'Invalid media.' ],
		],
		'shouldReportInvalidMediaForSuperAdminOnMultisite' => [
			'config'   => [
				'site'        => 'multisite',
				'super_admin' => true,
				'target'      => 'unknown',
			],
			'expected' => [ 'message' => 'Invalid media.' ],
		],
		'shouldReportNotOptimizedForAdminAttachmentOnMultisite' => [
			'config'   => [
				'site'        => 'multisite',
				'super_admin' => false,
				'target'      => 'attachment',
			],
			'expected' => [ 'message' => 'not optimized' ],
		],
		'shouldReportInvalidMediaForAdminOnNetworkActive' => [
			'config'   => [
				'site'        => 'network-active',
				'super_admin' => false,
				'target'      => 'unknown',
			],
			'expected' => [ 'message' => 'Invalid media.' ],
		],
		'shouldReportInvalidMediaForSuperAdminOnNetworkActive' => [
			'config'   => [
				'site'        => 'network-active',
				'super_admin' => true,
				'target'      => 'unknown',
			],
			'expected' => [ 'message' => 'Invalid media.' ],
		],
		'shouldReportNotOptimizedForAdminAttachmentOnNetworkActive' => [
			'config'   => [
				'site'        => 'network-active',
				'super_admin' => false,
				'target'      => 'attachment',
			],
			'expected' => [ 'message' => 'not optimized' ],
		],
	],
];

<?php

return [
	'test_data' => [
		'shouldTrimQuotaResponseForSiteAdmin' => [
			'config'   => [
				'site'        => 'network-active',
				'super_admin' => false,
				'over_quota'  => true,
			],
			'expected' => [
				'status'  => 'insufficient_quota',
				'present' => [ 'message' ],
				'absent'  => [ 'next_date_update', 'upgrade_url' ],
			],
		],
		'shouldKeepQuotaResponseForSuperAdmin' => [
			'config'   => [
				'site'        => 'network-active',
				'super_admin' => true,
				'over_quota'  => true,
			],
			'expected' => [
				'status'  => 'insufficient_quota',
				'present' => [ 'message', 'next_date_update', 'upgrade_url' ],
				'absent'  => [],
			],
		],
		'shouldTrimConfirmationForSiteAdmin' => [
			'config'   => [
				'site'        => 'network-active',
				'super_admin' => false,
				'over_quota'  => false,
			],
			'expected' => [
				'status'  => 'confirmation_required',
				'present' => [ 'message', 'impact', 'confirm_with' ],
				'absent'  => [ 'quota_remaining' ],
			],
		],
		'shouldKeepConfirmationForSuperAdmin' => [
			'config'   => [
				'site'        => 'network-active',
				'super_admin' => true,
				'over_quota'  => false,
			],
			'expected' => [
				'status'  => 'confirmation_required',
				'present' => [ 'message', 'impact', 'confirm_with', 'quota_remaining' ],
				'absent'  => [],
			],
		],
		'shouldKeepConfirmationForAdminOnSingleSite' => [
			'config'   => [
				'site'        => 'single',
				'super_admin' => false,
				'over_quota'  => false,
			],
			'expected' => [
				'status'  => 'confirmation_required',
				'present' => [ 'quota_remaining' ],
				'absent'  => [],
			],
		],
		'shouldKeepQuotaResponseForAdminWhenSiteActivated' => [
			'config'   => [
				'site'        => 'multisite',
				'super_admin' => false,
				'over_quota'  => true,
			],
			'expected' => [
				'status'  => 'insufficient_quota',
				'present' => [ 'next_date_update', 'upgrade_url' ],
				'absent'  => [],
			],
		],
	],
];

<?php

$notice_text = 'Imagify no longer bundles the MCP Adapter, which is now available as a standalone plugin on WordPress.org. To keep using MCP with Imagify, please install and activate the';

return [
	'test_data' => [
		'shouldDisplayForSuperAdminWhenNetworkActive' => [
			'config'   => [
				'site'        => 'network-active',
				'super_admin' => true,
				'dismissed'   => false,
			],
			'expected' => [
				'contains'     => [
					$notice_text,
					'/wp-admin/network/plugin-install.php?s=mcp-adapter',
					'action=imagify_dismiss_notice',
				],
				'not_contains' => [],
			],
		],
		'shouldDisplayForSuperAdminWhenSiteActivated' => [
			'config'   => [
				'site'        => 'multisite',
				'super_admin' => true,
				'dismissed'   => false,
			],
			'expected' => [
				'contains'     => [ $notice_text, '/wp-admin/network/plugin-install.php?s=mcp-adapter' ],
				'not_contains' => [],
			],
		],
		'shouldNotDisplayWhenDismissedWhenNetworkActive' => [
			'config'   => [
				'site'        => 'network-active',
				'super_admin' => true,
				'dismissed'   => true,
			],
			'expected' => [
				'contains'     => [],
				'not_contains' => [ 'MCP Adapter' ],
			],
		],
		'shouldNotDisplayForSiteAdminWhenNetworkActive' => [
			'config'   => [
				'site'        => 'network-active',
				'super_admin' => false,
				'dismissed'   => false,
			],
			'expected' => [
				'contains'     => [],
				'not_contains' => [ 'MCP Adapter' ],
			],
		],
	],
];

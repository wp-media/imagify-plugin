<?php

$notice_text = 'Imagify no longer bundles the MCP Adapter, which is now available as a standalone plugin on WordPress.org. To keep using MCP with Imagify, please install and activate the';

return [
	'test_data' => [
		'shouldDisplayNoticeForAdministratorWithSessions' => [
			'config'   => [
				'role'      => 'administrator',
				'meta'      => [ 'mcp_adapter_sessions' => [ 'session-1' ] ],
				'dismissed' => false,
			],
			'expected' => [
				'contains'     => [
					$notice_text,
					'plugin-install.php?s=mcp-adapter&#038;tab=search&#038;type=term',
					'action=imagify_dismiss_notice',
					'notice=mcp-adapter',
				],
				'not_contains' => [],
			],
		],
		'shouldDisplayNoticeForAdministratorWithOAuthRefreshToken' => [
			'config'   => [
				'role'      => 'administrator',
				'meta'      => [ 'mcp_refresh_jti_abc123' => 'x' ],
				'dismissed' => false,
			],
			'expected' => [
				'contains'     => [ $notice_text ],
				'not_contains' => [],
			],
		],
		'shouldNotDisplayNoticeWithoutMcpUsage'           => [
			'config'   => [
				'role'      => 'administrator',
				'meta'      => [],
				'dismissed' => false,
			],
			'expected' => [
				'contains'     => [],
				'not_contains' => [ 'MCP Adapter' ],
			],
		],
		'shouldNotDisplayNoticeForEditor'                 => [
			'config'   => [
				'role'      => 'editor',
				'meta'      => [ 'mcp_adapter_sessions' => [ 'session-1' ] ],
				'dismissed' => false,
			],
			'expected' => [
				'contains'     => [],
				'not_contains' => [ 'MCP Adapter' ],
			],
		],
		'shouldNotDisplayNoticeOnceDismissed'             => [
			'config'   => [
				'role'      => 'administrator',
				'meta'      => [ 'mcp_adapter_sessions' => [ 'session-1' ] ],
				'dismissed' => true,
			],
			'expected' => [
				'contains'     => [],
				'not_contains' => [ 'MCP Adapter' ],
			],
		],
	],
];

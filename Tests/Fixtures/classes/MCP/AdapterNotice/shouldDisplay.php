<?php

$defaults = [
	// Every flag defaults to the "notice applies" state, so each row only overrides what it checks.
	'abilities_api'   => true,
	'install_plugins' => true,
	'manage'          => true,
	'adapter_loaded'  => false,
	'user_id'         => 7,
	'blog_id'         => 1,
	// Maps a user meta key to its stored value (what get_user_meta( $id, $key, true ) returns).
	'meta'            => [],
];

return [
	'test_data' => [
		// Legacy network-global sessions key holding sessions.
		'shouldDisplayWhenLegacySessionsKeyIsNotEmpty'   => [
			'config'   => array_merge( $defaults, [ 'meta' => [ 'mcp_adapter_sessions' => [ 'session-1' ] ] ] ),
			'expected' => true,
		],

		// Sessions key scoped to the current site (MCP Adapter 0.7 on multisite).
		'shouldDisplayWhenSessionsKeyOfCurrentBlogIsNotEmpty' => [
			'config'   => array_merge(
				$defaults,
				[
					'blog_id' => 3,
					'meta'    => [ 'mcp_adapter_sessions_3' => [ 'session-1' ] ],
				]
			),
			'expected' => true,
		],

		// An OAuth refresh token proves a connected client, even without sessions.
		'shouldDisplayWhenOnlyAnOAuthRefreshTokenExists' => [
			'config'   => array_merge( $defaults, [ 'meta' => [ 'mcp_refresh_jti_abc123' => 'x' ] ] ),
			'expected' => true,
		],

		// Dismissing another notice does not hide this one.
		'shouldDisplayWhenAnotherNoticeIsDismissed'      => [
			'config'   => array_merge(
				$defaults,
				[
					'meta' => [
						'mcp_adapter_sessions'    => [ 'session-1' ],
						'_imagify_ignore_notices' => [ 'rating' ],
					],
				]
			),
			'expected' => true,
		],

		'shouldNotDisplayWhenAbilitiesApiIsUnavailable'  => [
			'config'   => array_merge(
				$defaults,
				[
					'abilities_api' => false,
					'meta'          => [ 'mcp_adapter_sessions' => [ 'session-1' ] ],
				]
			),
			'expected' => false,
		],

		'shouldNotDisplayWhenUserCannotInstallPlugins'   => [
			'config'   => array_merge(
				$defaults,
				[
					'install_plugins' => false,
					'meta'            => [ 'mcp_adapter_sessions' => [ 'session-1' ] ],
				]
			),
			'expected' => false,
		],

		'shouldNotDisplayWhenUserCannotManageImagify'    => [
			'config'   => array_merge(
				$defaults,
				[
					'manage' => false,
					'meta'   => [ 'mcp_adapter_sessions' => [ 'session-1' ] ],
				]
			),
			'expected' => false,
		],

		'shouldNotDisplayWhenAnAdapterIsLoaded'          => [
			'config'   => array_merge(
				$defaults,
				[
					'adapter_loaded' => true,
					'meta'           => [ 'mcp_adapter_sessions' => [ 'session-1' ] ],
				]
			),
			'expected' => false,
		],

		'shouldNotDisplayWhenUserHasNoMcpMeta'           => [
			'config'   => array_merge( $defaults, [ 'meta' => [ 'nickname' => 'admin' ] ] ),
			'expected' => false,
		],

		'shouldNotDisplayWhenLegacySessionsKeyIsEmpty'   => [
			'config'   => array_merge( $defaults, [ 'meta' => [ 'mcp_adapter_sessions' => [] ] ] ),
			'expected' => false,
		],

		'shouldNotDisplayWhenSessionsKeyIsAnEmptyString' => [
			'config'   => array_merge( $defaults, [ 'meta' => [ 'mcp_adapter_sessions_1' => '' ] ] ),
			'expected' => false,
		],

		// Another site's sessions on a multisite network are not this site's usage.
		'shouldNotDisplayWhenSessionsKeyBelongsToAnotherBlog' => [
			'config'   => array_merge(
				$defaults,
				[
					'blog_id' => 3,
					'meta'    => [ 'mcp_adapter_sessions_2' => [ 'session-1' ] ],
				]
			),
			'expected' => false,
		],

		'shouldNotDisplayWhenNoticeIsDismissed'          => [
			'config'   => array_merge(
				$defaults,
				[
					'meta' => [
						'mcp_adapter_sessions'    => [ 'session-1' ],
						'_imagify_ignore_notices' => [ 'rating', 'mcp-adapter' ],
					],
				]
			),
			'expected' => false,
		],

		'shouldNotDisplayWhenThereIsNoLoggedInUser'      => [
			'config'   => array_merge(
				$defaults,
				[
					'user_id' => 0,
					'meta'    => [ 'mcp_adapter_sessions' => [ 'session-1' ] ],
				]
			),
			'expected' => false,
		],
	],
];

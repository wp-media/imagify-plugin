<?php

$defaults = [
	'abilities_api'   => true,
	'install_plugins' => true,
	'manage'          => true,
	'adapter_loaded'  => false,
	'user_id'         => 7,
	'blog_id'         => 1,
	'meta'            => [],
];

return [
	'test_data' => [
		'shouldDisplayWhenLegacySessionsKeyIsNotEmpty'   => [
			'config'   => array_merge( $defaults, [ 'meta' => [ 'mcp_adapter_sessions' => [ 'session-1' ] ] ] ),
			'expected' => true,
		],

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

		'shouldDisplayWhenOnlyAnOAuthRefreshTokenExists' => [
			'config'   => array_merge( $defaults, [ 'meta' => [ 'mcp_refresh_jti_abc123' => 'x' ] ] ),
			'expected' => true,
		],

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

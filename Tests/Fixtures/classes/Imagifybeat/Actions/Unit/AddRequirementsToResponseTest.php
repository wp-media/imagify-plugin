<?php
/**
 * The requirements are sent to users of the settings and bulk optimization pages, third-party ones (here 'ngg') included.
 */

return [
	'test_data' => [
		'shouldNotSendWhenNotAsked'                => [
			'config'   => [
				'allowed' => [ 'wp:manage' ],
				'data'    => [],
			],
			'expected' => false,
		],
		'shouldNotSendToMediaLibraryUsers'         => [
			'config'   => [
				'allowed' => [ 'wp:optimize', 'custom-folders:optimize' ],
				'data'    => [ 'imagify_requirements' => 1 ],
			],
			'expected' => false,
		],
		'shouldSendToSettingsUsers'                => [
			'config'   => [
				'allowed' => [ 'wp:manage' ],
				'data'    => [ 'imagify_requirements' => 1 ],
			],
			'expected' => true,
		],
		'shouldSendToBulkOptimizationUsers'        => [
			'config'   => [
				'allowed' => [ 'wp:bulk-optimize' ],
				'data'    => [ 'imagify_requirements' => 1 ],
			],
			'expected' => true,
		],
		'shouldSendToNetworkBulkOptimizationUsers' => [
			'config'   => [
				'allowed' => [ 'custom-folders:bulk-optimize' ],
				'data'    => [ 'imagify_requirements' => 1 ],
			],
			'expected' => true,
		],
		'shouldSendToThirdPartyBulkOptimizationUsers' => [
			'config'   => [
				'allowed' => [ 'ngg:bulk-optimize' ],
				'data'    => [ 'imagify_requirements' => 1 ],
			],
			'expected' => true,
		],
		'shouldNotSendToThirdPartyMediaListUsers'  => [
			'config'   => [
				'allowed' => [ 'ngg:optimize' ],
				'data'    => [ 'imagify_requirements' => 1 ],
			],
			'expected' => false,
		],
	],
];

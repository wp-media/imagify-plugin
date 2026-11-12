<?php
/**
 * Imagifybeat is open to users who can use at least one Imagify screen, third-party contexts (here 'ngg') included:
 * "context:describer" pairs.
 */

return [
	'test_data' => [
		'shouldDenyWithoutAnyCapability'          => [
			'config'   => [ 'allowed' => [] ],
			'expected' => false,
		],
		'shouldDenyWithUnrelatedCapability'       => [
			'config'   => [ 'allowed' => [ 'wp:manual-optimize', 'wp:auto-optimize' ] ],
			'expected' => false,
		],
		'shouldAllowMediaLibraryUsers'            => [
			'config'   => [ 'allowed' => [ 'wp:optimize' ] ],
			'expected' => true,
		],
		'shouldAllowBulkOptimizationUsers'        => [
			'config'   => [ 'allowed' => [ 'wp:bulk-optimize' ] ],
			'expected' => true,
		],
		'shouldAllowSettingsUsers'                => [
			'config'   => [ 'allowed' => [ 'wp:manage' ] ],
			'expected' => true,
		],
		'shouldAllowOtherMediaUsers'              => [
			'config'   => [ 'allowed' => [ 'custom-folders:optimize' ] ],
			'expected' => true,
		],
		'shouldAllowNetworkBulkOptimizationUsers' => [
			'config'   => [ 'allowed' => [ 'custom-folders:bulk-optimize' ] ],
			'expected' => true,
		],
		'shouldAllowThirdPartyMediaListUsers'     => [
			'config'   => [ 'allowed' => [ 'ngg:optimize' ] ],
			'expected' => true,
		],
		'shouldAllowThirdPartyBulkOptimizationUsers' => [
			'config'   => [ 'allowed' => [ 'ngg:bulk-optimize' ] ],
			'expected' => true,
		],
		'shouldDenyWithUnrelatedThirdPartyCapability' => [
			'config'   => [ 'allowed' => [ 'ngg:manual-optimize', 'ngg:manage' ] ],
			'expected' => false,
		],
	],
];

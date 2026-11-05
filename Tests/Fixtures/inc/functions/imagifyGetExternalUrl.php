<?php

return [
	'test_data' => [
		'shouldReturnTheNextGenDeliveryDocumentation' => [
			'config'   => [
				'target' => 'documentation-nextgen-delivery',
			],
			'expected' => 'https://imagify.io/documentation/my-images-are-broken/',
		],

		'shouldReturnTheDocumentationHome'            => [
			'config'   => [
				'target' => 'documentation',
			],
			'expected' => 'https://imagify.io/documentation/',
		],

		'shouldReturnTheImagickGdDocumentation'       => [
			'config'   => [
				'target' => 'documentation-imagick-gd',
			],
			'expected' => 'https://imagify.io/documentation/solve-imagemagick-gd-required/',
		],

		'shouldReturnTheSubscriptionAppUrl'           => [
			'config'   => [
				'target' => 'subscription',
			],
			'expected' => 'https://app.imagify.io/#/subscription',
		],

		'shouldAppendQueryArgs'                       => [
			'config'   => [
				'target'     => 'documentation',
				'query_args' => [ 'utm_source' => 'imagify' ],
			],
			'expected' => 'https://imagify.io/documentation/?utm_source=imagify',
		],

		'shouldReturnAnEmptyStringForAnUnknownTarget' => [
			'config'   => [
				'target' => 'no-such-target',
			],
			'expected' => '',
		],
	],
];

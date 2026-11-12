<?php
/**
 * The Media Library status only contains media the user can optimize. A forbidden media and a missing one get the
 * same answer, whatever their lock state: no key at all.
 * 'statuses' is what the browser sends: '_{ID}' => whether it thinks the media is being optimized.
 * 'returned' lists the media whose status is sent.
 */

return [
	'test_data' => [
		'shouldNotSendWhenNotAsked'                        => [
			'config'   => [
				'allowed_ids' => [ 5 ],
				'locked_ids'  => [],
				'statuses'    => [],
			],
			'expected' => [],
		],
		'shouldSendChangedOwnMedia'                        => [
			'config'   => [
				'allowed_ids' => [ 5 ],
				'locked_ids'  => [],
				'statuses'    => [ 'wp' => [ '_5' => 1 ] ],
			],
			'expected' => [ 5 ],
		],
		'shouldNotSendUnchangedOwnMedia'                   => [
			'config'   => [
				'allowed_ids' => [ 5 ],
				'locked_ids'  => [],
				'statuses'    => [ 'wp' => [ '_5' => 0 ] ],
			],
			'expected' => [],
		],
		'shouldOnlySendAllowedMedia'                       => [
			'config'   => [
				'allowed_ids' => [ 5 ],
				'locked_ids'  => [],
				'statuses'    => [
					'wp' => [
						'_5'    => 1,
						'_6'    => 1,
						'_9999' => 1,
					],
				],
			],
			'expected' => [ 5 ],
		],
		'shouldSendNothingForForbiddenMedia'               => [
			'config'   => [
				'allowed_ids' => [],
				'locked_ids'  => [],
				'statuses'    => [ 'wp' => [ '_6' => 1 ] ],
			],
			'expected' => [],
		],
		'shouldSendNothingForMissingMedia'                 => [
			'config'   => [
				'allowed_ids' => [],
				'locked_ids'  => [],
				'statuses'    => [ 'wp' => [ '_9999' => 1 ] ],
			],
			'expected' => [],
		],
		'shouldSendNothingForLockedForbiddenMediaSeenIdle' => [
			'config'   => [
				'allowed_ids' => [],
				'locked_ids'  => [ 6 ],
				'statuses'    => [ 'wp' => [ '_6' => 0 ] ],
			],
			'expected' => [],
		],
		'shouldSendNothingForMissingMediaSeenIdle'         => [
			'config'   => [
				'allowed_ids' => [],
				'locked_ids'  => [ 6 ],
				'statuses'    => [ 'wp' => [ '_9999' => 0 ] ],
			],
			'expected' => [],
		],
		'shouldSendNothingForLockedForbiddenMediaSeenBusy' => [
			'config'   => [
				'allowed_ids' => [],
				'locked_ids'  => [ 6 ],
				'statuses'    => [ 'wp' => [ '_6' => 1 ] ],
			],
			'expected' => [],
		],
	],
];

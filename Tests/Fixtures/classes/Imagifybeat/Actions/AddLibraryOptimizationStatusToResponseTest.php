<?php
/**
 * The Media Library status only contains media the user can edit. Another user's media and a missing media get the
 * same answer, whether or not the other user's media is being optimized: nothing at all.
 *
 * 'media': whose media is asked for ('own', 'other', or 'missing'). 'locked': whether the other user's media is being
 * optimized. 'seen_locked': what the browser sends.
 */

return [
	'test_data' => [
		'shouldReturnChangedOwnMediaToAuthor'          => [
			'config'   => [
				'role'        => 'author',
				'media'       => 'own',
				'locked'      => false,
				'seen_locked' => 1,
			],
			'expected' => true,
		],
		'shouldNotReturnUnchangedOwnMedia'             => [
			'config'   => [
				'role'        => 'author',
				'media'       => 'own',
				'locked'      => false,
				'seen_locked' => 0,
			],
			'expected' => false,
		],
		'shouldReturnOtherUsersMediaToEditor'          => [
			'config'   => [
				'role'        => 'editor',
				'media'       => 'other',
				'locked'      => false,
				'seen_locked' => 1,
			],
			'expected' => true,
		],
		'shouldNotReturnOtherUsersMediaToAuthor'       => [
			'config'   => [
				'role'        => 'author',
				'media'       => 'other',
				'locked'      => false,
				'seen_locked' => 1,
			],
			'expected' => false,
		],
		'shouldNotReturnMissingMedia'                  => [
			'config'   => [
				'role'        => 'author',
				'media'       => 'missing',
				'locked'      => false,
				'seen_locked' => 1,
			],
			'expected' => false,
		],
		'shouldNotRevealLockedOtherUsersMediaSeenIdle' => [
			'config'   => [
				'role'        => 'author',
				'media'       => 'other',
				'locked'      => true,
				'seen_locked' => 0,
			],
			'expected' => false,
		],
		'shouldAnswerMissingMediaTheSameWaySeenIdle'   => [
			'config'   => [
				'role'        => 'author',
				'media'       => 'missing',
				'locked'      => true,
				'seen_locked' => 0,
			],
			'expected' => false,
		],
		'shouldNotRevealLockedOtherUsersMediaSeenBusy' => [
			'config'   => [
				'role'        => 'author',
				'media'       => 'other',
				'locked'      => true,
				'seen_locked' => 1,
			],
			'expected' => false,
		],
		'shouldAnswerMissingMediaTheSameWaySeenBusy'   => [
			'config'   => [
				'role'        => 'author',
				'media'       => 'missing',
				'locked'      => true,
				'seen_locked' => 1,
			],
			'expected' => false,
		],
	],
];

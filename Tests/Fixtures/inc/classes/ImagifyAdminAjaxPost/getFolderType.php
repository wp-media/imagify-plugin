<?php

return [
	'test_data' => [
		'shouldReadFolderTypeFromGetByDefault' => [
			'config'   => [
				'get'  => [ 'folder_type' => 'library' ],
				'post' => [],
				'args' => [],
			],
			'expected' => 'library',
		],

		'shouldReadFolderTypeFromPostWhenAsked' => [
			'config'   => [
				'get'  => [ 'folder_type' => 'library' ],
				'post' => [ 'folder_type' => 'custom-folders' ],
				'args' => [ 'POST' ],
			],
			'expected' => 'custom-folders',
		],

		'shouldSanitizeFolderType' => [
			'config'   => [
				'get'  => [ 'folder_type' => 'library <b>' ],
				'post' => [],
				'args' => [],
			],
			'expected' => 'library',
		],

		'shouldReturnNoopWhenMissing' => [
			'config'   => [
				'get'  => [],
				'post' => [],
				'args' => [],
			],
			'expected' => 'noop',
		],

		'shouldReturnNoopWhenOnlySentWithTheOtherMethod' => [
			'config'   => [
				'get'  => [ 'folder_type' => 'library' ],
				'post' => [],
				'args' => [ 'POST' ],
			],
			'expected' => 'noop',
		],
	],
];

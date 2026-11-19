<?php

return [
	'test_data' => [
		'shouldIgnoreCustomFolderFilesWhenNetworkActive' => [
			'config'   => [ 'site' => 'network-active' ],
			'expected' => [
				'count' => 0,
				'total' => 5,
			],
		],
		'shouldCountCustomFolderFilesOnSingleSite'       => [
			'config'   => [ 'site' => 'single' ],
			'expected' => [
				'count' => 1,
				'total' => 6,
			],
		],
	],
];

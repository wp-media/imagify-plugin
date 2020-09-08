<?php

return [
	'vfs_dir' => 'public/',
	//'structure' => [],

	'test_data' => [
		'directoryCreatedNewly' => [
			'config' => [
				'dir_name' => 'imagify-backup',
			],
			'expected' => [
				'created' => true,
			]
		],

		'directoryExistsBefore' => [
			'config' => [
				'dir_name' => 'wp-content',
			],
			'expected' => [
				'created' => true,
			]
		],
	]

];

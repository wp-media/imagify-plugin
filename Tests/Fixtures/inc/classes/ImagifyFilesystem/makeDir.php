<?php

return [
	'vfs_dir' => 'public/',
	//'structure' => [],

	'test_data' => [
		'directoryExistsBefore' => [
			'config' => [
				'dir_name' => 'wp-content',
			],
			'expected' => [
				'created' => true,
			]
		]
	]

];

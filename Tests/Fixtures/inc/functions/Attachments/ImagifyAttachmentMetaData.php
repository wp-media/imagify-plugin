<?php
return [
	'test_data' => [
		'testAttachmentsExistWithoutMetadata' => [
			'config' => [
				'statuses' => [
					'publish' => 'Publish',
					'inherit' => 'Inherit',
					'private' => 'Private',
					'future' => 'Future'
				],

			],
			'expected' => true
		],
		/*'testNoAttachmentsExistWithoutMetadata' => [
			'config' => [
				'statuses' => [],

			],
			'expected' => true
		]*/
	],
];

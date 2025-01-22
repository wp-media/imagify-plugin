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
				'reset' => true

			],
			'expected' => true
		],
		'testNoAttachmentsExistWithoutMetadata' => [
			'config' => [
				'statuses' => [],
				'reset' => true
			],
			'expected' => false
		]
	],
];

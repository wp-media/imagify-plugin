<?php

return [
	'test_data' => [
		// #1210 regression guard: a WP-scaled image must resolve to the original-derived path,
		// not the scaled path stored in `_wp_attached_file`.
		'shouldResolveOriginalPathForScaledImage' => [
			'config'   => [
				'file_path' => '/uploads/2024/01/image-scaled.jpg',
				'metadata'  => [
					'original_image' => 'image.jpg',
				],
			],
			'expected' => [
				'result' => '/uploads/2024/01/image.jpg',
			],
		],

		// Non-scaled image: metadata has no `original_image`, path must be unchanged.
		'shouldReturnFilePathUnchangedWhenNotScaled' => [
			'config'   => [
				'file_path' => '/uploads/2024/01/image.jpg',
				'metadata'  => [
					'width'  => 800,
					'height' => 600,
				],
			],
			'expected' => [
				'result' => '/uploads/2024/01/image.jpg',
			],
		],

		// Metadata is null (e.g. no `_wp_attachment_metadata` row) must not error and must fall
		// back to the given file path unchanged.
		'shouldReturnFilePathUnchangedWhenMetadataIsNull' => [
			'config'   => [
				'file_path' => '/uploads/2024/01/image.jpg',
				'metadata'  => null,
			],
			'expected' => [
				'result' => '/uploads/2024/01/image.jpg',
			],
		],

		// Garbage/non-array metadata (e.g. unserialization failed and returned the raw string)
		// must not error and must fall back to the given file path unchanged.
		'shouldReturnFilePathUnchangedWhenMetadataIsGarbageString' => [
			'config'   => [
				'file_path' => '/uploads/2024/01/image.jpg',
				'metadata'  => 'not-serialized-data',
			],
			'expected' => [
				'result' => '/uploads/2024/01/image.jpg',
			],
		],

		// `original_image` present but empty must fall back to the given file path unchanged.
		'shouldReturnFilePathUnchangedWhenOriginalImageIsEmpty' => [
			'config'   => [
				'file_path' => '/uploads/2024/01/image.jpg',
				'metadata'  => [
					'original_image' => '',
				],
			],
			'expected' => [
				'result' => '/uploads/2024/01/image.jpg',
			],
		],

		// `original_image` present but not a string (corrupted metadata) must fall back to the
		// given file path unchanged, without triggering a PHP warning during concatenation.
		'shouldReturnFilePathUnchangedWhenOriginalImageIsNotAString' => [
			'config'   => [
				'file_path' => '/uploads/2024/01/image.jpg',
				'metadata'  => [
					'original_image' => [ 'not', 'a', 'string' ],
				],
			],
			'expected' => [
				'result' => '/uploads/2024/01/image.jpg',
			],
		],
	],
];

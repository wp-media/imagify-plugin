<?php

return [
	'test_data' => [
		// The next-gen file actually exists on disk: not missing.
		'shouldReturnFalseWhenAvifFileExists'   => [
			'config'   => [
				'size'          => 'thumbnail@imagify-avif',
				'original_path' => '/uploads/thumbnail.jpg',
				'next_gen_path' => '/uploads/thumbnail.jpg.avif',
				'file_exists'   => true,
			],
			'expected' => [
				'result' => false,
			],
		],

		// The data says success, but the AVIF file is gone: this is the #816 stale-data case.
		'shouldReturnTrueWhenAvifFileIsMissing' => [
			'config'   => [
				'size'          => 'thumbnail@imagify-avif',
				'original_path' => '/uploads/thumbnail.jpg',
				'next_gen_path' => '/uploads/thumbnail.jpg.avif',
				'file_exists'   => false,
			],
			'expected' => [
				'result' => true,
			],
		],

		// Same, for the WebP format.
		'shouldReturnFalseWhenWebpFileExists'   => [
			'config'   => [
				'size'          => 'thumbnail@imagify-webp',
				'original_path' => '/uploads/thumbnail.jpg',
				'next_gen_path' => '/uploads/thumbnail.jpg.webp',
				'file_exists'   => true,
			],
			'expected' => [
				'result' => false,
			],
		],

		'shouldReturnTrueWhenWebpFileIsMissing' => [
			'config'   => [
				'size'          => 'thumbnail@imagify-webp',
				'original_path' => '/uploads/thumbnail.jpg',
				'next_gen_path' => '/uploads/thumbnail.jpg.webp',
				'file_exists'   => false,
			],
			'expected' => [
				'result' => true,
			],
		],
	],
];

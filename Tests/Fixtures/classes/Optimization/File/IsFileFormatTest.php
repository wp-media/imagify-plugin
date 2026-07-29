<?php

return [
	'test_data' => [
		// A genuine WebP payload requested as 'webp' must be recognized.
		'shouldDetectGenuineWebp'           => [
			'config'   => [
				'header' => "RIFF\x00\x00\x00\x00WEBP",
				'format' => 'webp',
			],
			'expected' => [
				'result' => true,
			],
		],

		// A genuine AVIF payload (brand "avif") requested as 'avif' must be recognized.
		'shouldDetectGenuineAvifBrand'      => [
			'config'   => [
				'header' => "\x00\x00\x00\x1cftypavif",
				'format' => 'avif',
			],
			'expected' => [
				'result' => true,
			],
		],

		// The "avis" brand (AVIF image sequence) must also be recognized as AVIF.
		'shouldDetectGenuineAvifAvisBrand'  => [
			'config'   => [
				'header' => "\x00\x00\x00\x1cftypavis",
				'format' => 'avif',
			],
			'expected' => [
				'result' => true,
			],
		],

		// This is the #816 failure mode: the API returned the original (non-converted) JPEG
		// bytes instead of a WebP file. Must NOT be mistaken for the requested format.
		'shouldRejectOriginalBytesAsWebp'   => [
			'config'   => [
				'header' => "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01",
				'format' => 'webp',
			],
			'expected' => [
				'result' => false,
			],
		],

		// Same failure mode, requested as AVIF.
		'shouldRejectOriginalBytesAsAvif'   => [
			'config'   => [
				'header' => "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01",
				'format' => 'avif',
			],
			'expected' => [
				'result' => false,
			],
		],

		// A WebP file must not be mistaken for an AVIF file.
		'shouldRejectWebpAsAvif'            => [
			'config'   => [
				'header' => "RIFF\x00\x00\x00\x00WEBP",
				'format' => 'avif',
			],
			'expected' => [
				'result' => false,
			],
		],

		// An AVIF file must not be mistaken for a WebP file.
		'shouldRejectAvifAsWebp'            => [
			'config'   => [
				'header' => "\x00\x00\x00\x1cftypavif",
				'format' => 'webp',
			],
			'expected' => [
				'result' => false,
			],
		],

		// A too-short/empty payload can't be a valid next-gen file.
		'shouldRejectTruncatedContent'      => [
			'config'   => [
				'header' => 'short',
				'format' => 'webp',
			],
			'expected' => [
				'result' => false,
			],
		],

		// If the content couldn't be read at all, treat it as a mismatch (fail safe).
		'shouldRejectWhenContentUnreadable' => [
			'config'   => [
				'header' => false,
				'format' => 'webp',
			],
			'expected' => [
				'result' => false,
			],
		],
	],
];

<?php

return [
	'test_data' => [
		// This is the actual #816 regression: a next-gen request whose API response carries a
		// `message` and returns bytes that are NOT the requested format (the original, unconverted
		// bytes). No next-gen file must be written, and critically the source file must not be
		// overwritten either.
		'shouldRejectNonMatchingBytesForNextGenRequest' => [
			'config'   => [
				'convert'   => 'avif',
				'message'   => 'WELL DONE. This image is already compressed, no further compression required',
				'temp_body' => "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01",
			],
			'expected' => [
				'result'        => 'wp_error',
				'error_code'    => 'no_next_gen_returned',
				'error_message' => 'WELL DONE. This image is already compressed, no further compression required',
				'move_called'   => false,
				'delete_called' => true,
				'destination'   => null,
			],
		],

		// Same trap, but the API response carries the other known message. The raw substring
		// "This image is already compressed" must survive verbatim into the WP_Error message:
		// AbstractProcess::update_size_optimization_data() greps for it to resolve the status to
		// `already_optimized`, which is what unblocks the self-healing recovery path.
		'shouldPreserveRawAlreadyCompressedSubstring'   => [
			'config'   => [
				'convert'   => 'webp',
				'message'   => 'WELL DONE. This image is already compressed, no further compression required',
				'temp_body' => "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01",
			],
			'expected' => [
				'result'        => 'wp_error',
				'error_code'    => 'no_next_gen_returned',
				'error_message' => 'WELL DONE. This image is already compressed, no further compression required',
				'move_called'   => false,
				'delete_called' => true,
				'destination'   => null,
			],
		],

		// A next-gen request whose response carries a `message` but returns genuine next-gen
		// bytes must still be written to the next-gen path (a `message` isn't always fatal).
		'shouldAcceptMatchingBytesForNextGenRequest'    => [
			'config'   => [
				'convert'   => 'avif',
				'message'   => 'Some informational message',
				'temp_body' => "\x00\x00\x00\x1cftypavif",
			],
			'expected' => [
				'result'        => 'response',
				'move_called'   => true,
				'delete_called' => false,
				'destination'   => 'next_gen',
			],
		],

		// The #751 guard: a non-conversion request whose response carries a `message` must behave
		// exactly as before this fix — `convert` cleared, destination is the source path, no
		// content verification performed.
		'shouldClearConvertForNonNextGenRequest'        => [
			'config'   => [
				'convert'   => '',
				'message'   => 'WELL DONE. This image is already compressed, no further compression required',
				'temp_body' => "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01",
			],
			'expected' => [
				'result'        => 'response',
				'move_called'   => true,
				'delete_called' => false,
				'destination'   => 'source',
			],
		],
	],
];

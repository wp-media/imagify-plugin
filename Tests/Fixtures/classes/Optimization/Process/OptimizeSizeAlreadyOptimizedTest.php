<?php

return [
	'test_data' => [
		// The data says success and the next-gen file is actually present: must still bail out
		// with the "already optimized" error, exactly as before this fix.
		'shouldBailOutWhenNextGenFileExists'       => [
			'config'   => [
				'file_exists' => true,
			],
			'expected' => [
				'bails_out' => true,
			],
		],

		// The data says success but the next-gen file is missing on disk (the #816 stale-data
		// case): must NOT bail out, and instead fall through so the file gets regenerated.
		'shouldNotBailOutWhenNextGenFileIsMissing' => [
			'config'   => [
				'file_exists' => false,
			],
			'expected' => [
				'bails_out' => false,
			],
		],
	],
];

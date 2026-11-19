<?php

return [
	'test_data' => [
		'shouldReturnTrueOnBulkPage' => [
			'config'   => [
				'pagenow' => 'upload.php',
				'page'    => 'imagify-bulk-optimization',
			],
			'expected' => true,
		],

		'shouldReturnFalseWithoutPageArgument' => [
			'config'   => [
				'pagenow' => 'upload.php',
				'page'    => null,
			],
			'expected' => false,
		],

		'shouldReturnFalseOnAnotherPage' => [
			'config'   => [
				'pagenow' => 'upload.php',
				'page'    => 'imagify-files',
			],
			'expected' => false,
		],

		'shouldReturnFalseOnAnotherScreen' => [
			'config'   => [
				'pagenow' => 'options-general.php',
				'page'    => 'imagify-bulk-optimization',
			],
			'expected' => false,
		],

		'shouldReturnFalseWhenPageIsNotAString' => [
			'config'   => [
				'pagenow' => 'upload.php',
				'page'    => [ 'imagify-bulk-optimization' ],
			],
			'expected' => false,
		],
	],
];

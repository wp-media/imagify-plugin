<?php

return [
	'test_data' => [
		'shouldReturnTrueOnFilesPage' => [
			'config'   => [
				'pagenow' => 'upload.php',
				'page'    => 'imagify-files',
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
				'page'    => 'imagify-bulk-optimization',
			],
			'expected' => false,
		],

		'shouldReturnFalseOnAnotherScreen' => [
			'config'   => [
				'pagenow' => 'options-general.php',
				'page'    => 'imagify-files',
			],
			'expected' => false,
		],
	],
];

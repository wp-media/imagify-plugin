<?php

// `nonce` is the action the submitted nonce was created for; null means no nonce is sent.
return [
	'test_data' => [
		'shouldRedirectToBulkWhenNonceIsValid' => [
			'config'   => [
				'nonce'            => 'imagify-options',
				'submit_goto_bulk' => true,
			],
			'expected' => [
				'redirect' => 'upload.php?page=imagify-bulk-optimization',
			],
		],

		'shouldNotRedirectWithoutTheBulkButton' => [
			'config'   => [
				'nonce'            => 'imagify-options',
				'submit_goto_bulk' => false,
			],
			'expected' => [
				'redirect' => null,
			],
		],

		'shouldNotRedirectWithoutNonce' => [
			'config'   => [
				'nonce'            => null,
				'submit_goto_bulk' => true,
			],
			'expected' => [
				'redirect' => null,
			],
		],

		'shouldNotRedirectWithNonceForAnotherAction' => [
			'config'   => [
				'nonce'            => 'another-action',
				'submit_goto_bulk' => true,
			],
			'expected' => [
				'redirect' => null,
			],
		],
	],
];

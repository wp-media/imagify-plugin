<?php

return [
	'test_data' => [
		'shouldKeepLineBreaks' => [
			'config'   => [
				'info' => 'First sentence.<br><br>Second sentence.',
			],
			'expected' => 'First sentence.<br><br>Second sentence.',
		],

		'shouldKeepSelfClosingLineBreaks' => [
			'config'   => [
				'info' => 'First sentence.<br />Second sentence.',
			],
			'expected' => 'First sentence.<br />Second sentence.',
		],

		'shouldKeepCodeTags' => [
			'config'   => [
				'info' => 'Defined by the <code>imagify_nextgen_images_format</code> filter.',
			],
			'expected' => 'Defined by the <code>imagify_nextgen_images_format</code> filter.',
		],

		'shouldKeepDocumentationLink' => [
			'config'   => [
				'info' => 'See <a href="https://imagify.io/documentation/" target="_blank">Read more</a>.',
			],
			'expected' => 'See <a href="https://imagify.io/documentation/" target="_blank">Read more</a>.',
		],

		'shouldKeepInlineEmphasis' => [
			'config'   => [
				'info' => 'This is <strong>important</strong> and <em>subtle</em>.',
			],
			'expected' => 'This is <strong>important</strong> and <em>subtle</em>.',
		],

		'shouldStripScriptTag' => [
			'config'   => [
				'info' => 'Harmless.<script>alert(1)</script>',
			],
			'expected' => 'Harmless.alert(1)',
		],

		'shouldStripEventHandlerAttribute' => [
			'config'   => [
				'info' => '<a href="https://imagify.io/" onclick="alert(1)">Link</a>',
			],
			'expected' => '<a href="https://imagify.io/">Link</a>',
		],

		'shouldStripDisallowedTagButKeepItsText' => [
			'config'   => [
				'info' => 'Before <iframe src="https://evil.test/"></iframe>after.',
			],
			'expected' => 'Before after.',
		],

		'shouldPrintNothingForAnEmptyMessage' => [
			'config'   => [
				'info' => '',
			],
			'expected' => '',
		],
	],
];

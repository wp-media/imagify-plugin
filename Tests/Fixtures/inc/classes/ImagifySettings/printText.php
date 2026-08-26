<?php

return [
	'test_data' => [
		'shouldKeepLineBreaks' => [
			'config'   => [
				'text' => 'First sentence.<br><br>Second sentence.',
			],
			'expected' => 'First sentence.<br><br>Second sentence.',
		],

		'shouldKeepSelfClosingLineBreaks' => [
			'config'   => [
				'text' => 'First sentence.<br />Second sentence.',
			],
			'expected' => 'First sentence.<br />Second sentence.',
		],

		'shouldKeepCodeTags' => [
			'config'   => [
				'text' => 'Defined by the <code>imagify_nextgen_images_format</code> filter.',
			],
			'expected' => 'Defined by the <code>imagify_nextgen_images_format</code> filter.',
		],

		'shouldKeepDocumentationLink' => [
			'config'   => [
				'text' => 'See <a href="https://imagify.io/documentation/" target="_blank">Read more</a>.',
			],
			'expected' => 'See <a href="https://imagify.io/documentation/" target="_blank">Read more</a>.',
		],

		'shouldKeepInlineEmphasis' => [
			'config'   => [
				'text' => 'This is <strong>important</strong> and <em>subtle</em>.',
			],
			'expected' => 'This is <strong>important</strong> and <em>subtle</em>.',
		],

		'shouldStripScriptTag' => [
			'config'   => [
				'text' => 'Harmless.<script>alert(1)</script>',
			],
			'expected' => 'Harmless.alert(1)',
		],

		'shouldStripEventHandlerAttribute' => [
			'config'   => [
				'text' => '<a href="https://imagify.io/" onclick="alert(1)">Link</a>',
			],
			'expected' => '<a href="https://imagify.io/">Link</a>',
		],

		'shouldStripDisallowedTagButKeepItsText' => [
			'config'   => [
				'text' => 'Before <iframe src="https://evil.test/"></iframe>after.',
			],
			'expected' => 'Before after.',
		],

		'shouldPrintNothingForAnEmptyMessage' => [
			'config'   => [
				'text' => '',
			],
			'expected' => '',
		],

		'shouldItaliciseTheRecommendedRadioChoice' => [
			'config'   => [
				'text' => 'Use &lt;picture&gt; tags <em>(preferred)</em>',
			],
			'expected' => 'Use &lt;picture&gt; tags <em>(preferred)</em>',
		],

		'shouldNotEscapeAThumbnailSizeEntityTwice' => [
			'config'   => [
				'text' => 'medium - 300 &times; 300',
			],
			'expected' => 'medium - 300 &times; 300',
		],

		'shouldKeepAnAlreadyEscapedLabelInert' => [
			'config'   => [
				'text' => 'My &lt;b&gt;size&lt;/b&gt; - 100 &times; 100',
			],
			'expected' => 'My &lt;b&gt;size&lt;/b&gt; - 100 &times; 100',
		],

		'shouldLeaveAJavascriptTemplatePlaceholderAlone' => [
			'config'   => [
				'text' => '{{ data.label }}',
			],
			'expected' => '{{ data.label }}',
		],
	],
];

<?php

$doc_link = '<a href="https://imagify.io/documentation/imagify-mcp/" target="_blank" rel="noopener noreferrer">Learn more about MCP</a>';

return [
	'test_data' => [
		'shouldRenderCheckedToggleWhenEnabled'    => [
			'config'   => [
				'mcp' => 1,
			],
			'expected' => [
				'contains'     => [
					'name="imagify_settings[mcp]"',
					'Connect your AI assistant to optimize and manage your images.',
					$doc_link,
					"checked='checked'",
				],
				'not_contains' => [],
			],
		],
		'shouldRenderUncheckedToggleWhenDisabled' => [
			'config'   => [
				'mcp' => 0,
			],
			'expected' => [
				'contains'     => [
					'name="imagify_settings[mcp]"',
					$doc_link,
				],
				'not_contains' => [
					"checked='checked'",
				],
			],
		],
	],
];

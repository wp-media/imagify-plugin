<?php

use Imagify\MCP\AbilitiesSubscriber;
use Imagify\MCP\AdapterNotice;
use Imagify\MCP\ConfigSubscriber;

return [
	'test_data' => [
		'shouldReturnMcpSubscribers' => [
			'expected' => [
				ConfigSubscriber::class,
				AbilitiesSubscriber::class,
				AdapterNotice::class,
			],
		],
	],
];

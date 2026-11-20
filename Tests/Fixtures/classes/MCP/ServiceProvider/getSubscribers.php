<?php

use Imagify\MCP\AbilitiesSubscriber;
use Imagify\MCP\AdapterNotice;
use Imagify\MCP\ConfigSubscriber;
use Imagify\MCP\SettingsSection;

return [
	'test_data' => [
		'shouldReturnMcpSubscribers' => [
			'expected' => [
				ConfigSubscriber::class,
				AbilitiesSubscriber::class,
				AdapterNotice::class,
				SettingsSection::class,
			],
		],
	],
];

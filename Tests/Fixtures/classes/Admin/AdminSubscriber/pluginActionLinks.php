<?php

return [
	'test_data' => [
		'testShouldReturnDocumentationLinkAmongPluginLinksIfPlanLabelIsNotStarter' => [
			'config'   => [
				'plan_id'     => 2,
			],
			'expected' => [
                'Documentation'
            ],
		],
		'testShouldReturnUpgradeLinkAmongPluginLinksIfPlanLabelIsStarter' => [
			'config'   => [
				'plan_id'     => 1,
			],
			'expected' => [
                'Upgrade',
                'class="imagify-plugin-upgrade"'
            ],
		],
	]
];

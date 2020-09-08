<?php

return [
	'wp-content' => [
		'index.php' => '',
		'themes' => [
			'twentytwenty' => [
				'style.php' => 'test',
				'assets'    => [
					'script.php' => 'test',
				],
			],
		],

		'plugins' => [
			'hello-dolly' => [
				'style.php'  => '',
				'script.php' => '',
			],
		],

		'uploads' => [],

		'advanced-cache.php' => '<?php $var = "Some contents.";',
	],
	'.htaccess'  => "# Random\n# add a trailing slash to /wp-admin# BEGIN WordPress\n\n# BEGIN WP Rocket\nPrevious rules.\n# END WP Rocket\n",
	'wp-config.php' => "<?php\ndefine( 'DB_NAME', 'local' );\ndefine( 'DB_USER', 'root' );\n",
];

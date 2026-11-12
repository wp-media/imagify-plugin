<?php

return [
	'test_data' => [
		'shouldAllowAccessWhenUserHasPermission' => [
			'config'   => [ 'has_permission' => true ],
			'expected' => [ 'is_error' => false ],
		],
		'shouldDenyAccessWhenUserLacksPermission' => [
			'config'   => [ 'has_permission' => false ],
			'expected' => [ 'is_error' => true ],
		],
	],
];

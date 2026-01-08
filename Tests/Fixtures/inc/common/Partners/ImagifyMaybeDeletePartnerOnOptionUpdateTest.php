<?php
return [
	'testShouldDeletePartnerOnOptionUpdate' => [
		'old_value' => [ 'api_key' => '' ],
		'new_value' => [ 'api_key' => 'SOME_VALID_API_KEY' ],
		'partner'   => 'extendify',
		'expected' => true,
	],
];

<?php
return [
	'testShouldReturnSameWhenOptionNotPresent' => [
		'value'    => false,
		'option'   => false,
		'expected' => false,
	],
	'testShouldReturnTrueWhenOptionPresent'    => [
		'value'    => false,
		'option'   => 'some_site_id',
		'expected' => true,
	],
];

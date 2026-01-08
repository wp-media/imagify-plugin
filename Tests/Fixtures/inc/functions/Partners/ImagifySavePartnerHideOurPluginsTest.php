<?php
return [
	'testShouldDoNothingWhenPartnerIsNotInList' => [
		'partner'  => 'unknown_partner',
		'expected' => false,
	],
	'testShouldSaveWhenPartnerIsInList'        => [
		'partner'  => 'extendify',
		'expected' => 'extendify',
	],
];

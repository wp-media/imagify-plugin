<?php

use Imagify\Abilities\BulkOptimize;
use Imagify\Abilities\GenerateMissingNextgen;
use Imagify\Abilities\GetAccount;
use Imagify\Abilities\GetMediaStatus;
use Imagify\Abilities\GetNextgenCoverage;
use Imagify\Abilities\GetSettings;
use Imagify\Abilities\GetStats;
use Imagify\Abilities\OptimizeMedia;
use Imagify\Abilities\RestoreMedia;
use Imagify\Abilities\UpdateSettings;
use Imagify\Bulk\Bulk;
use Imagify\MCP\AbilitiesSubscriber;
use Imagify\MCP\AdapterNotice;
use Imagify\MCP\ConfigSubscriber;

$provided = [
	AdapterNotice::class,
	ConfigSubscriber::class,
	AbilitiesSubscriber::class,
	Bulk::class,
	BulkOptimize::class,
	GenerateMissingNextgen::class,
	GetAccount::class,
	GetMediaStatus::class,
	GetNextgenCoverage::class,
	GetSettings::class,
	GetStats::class,
	OptimizeMedia::class,
	RestoreMedia::class,
	UpdateSettings::class,
];

$test_data = [];

foreach ( $provided as $service ) {
	$test_data[ 'shouldProvide' . substr( (string) strrchr( $service, '\\' ), 1 ) ] = [
		'service'  => $service,
		'expected' => true,
	];
}

$test_data['shouldNotProvideUnknownService'] = [
	'service'  => 'some_unknown_service',
	'expected' => false,
];

return [
	'test_data' => $test_data,
];

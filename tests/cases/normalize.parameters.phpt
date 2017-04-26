<?php

namespace SchematiconTests;

use Schematicon\ApiValidator\Loader;
use Schematicon\ApiValidator\Normalizer;
use Tester\Assert;


require_once __DIR__ . '/../bootstrap.php';


$loader = new Loader();
$normalizer = new Normalizer();

$apiSchema = $normalizer->normalize($loader->run(__DIR__ . '/input/api_normalize.parameters.neon'));


Assert::same(
	[
		'type' => 'map',
		'properties' => [
			'global_int' => [
				'type' => 'int|null',
			],
			'local_int' => [
				'type' => 'int',
			],
			'global_float' => [
				'type' => 'float',
			],
		],
	],
	$apiSchema['sections'][0]['endpoints']['/parameters']['get']['parameters']
);

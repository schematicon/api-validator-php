<?php

namespace SchematiconTests;

use Schematicon\ApiValidator\Loader;
use Schematicon\ApiValidator\Normalizer;
use Tester\Assert;


require_once __DIR__ . '/../bootstrap.php';


$loader = new Loader();
$normalizer = new Normalizer();

$apiSchema = $normalizer->normalize($loader->run(__DIR__ . '/input/api_normalize.wrappers.neon'));


Assert::same(
	[
		'type' => 'map',
		'properties' => [
			'status' => [
				'type' => 'int',
			],
			'response' => [
				'type' => 'int',
			],
		],
	],
	$apiSchema['sections'][0]['endpoints']['/default_wrapper']['get']['response_ok']['schema']
);


Assert::same(
	[
		'type' => 'string',
	],
	$apiSchema['sections'][0]['endpoints']['/custom_wrapper']['get']['response_ok']['schema']
);


Assert::same(
	[
		'type' => 'map',
		'properties' => [
			'data' => [
				'type' => 'string',
			],
		],
	],
	$apiSchema['sections'][0]['endpoints']['/custom_wrapper2']['get']['response_ok']['schema']
);

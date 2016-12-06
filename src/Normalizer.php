<?php

/**
 * This file is part of the Schematicon library.
 * @license    MIT
 * @link       https://github.com/schematicon/api-validator
 */

namespace Schematicon\ApiValidator;

use Schematicon\Validator\Normalizer as SchematiconNormalizer;
use Schematicon\Validator\SchemaValidator;


class Normalizer
{
	/** @var SchemaValidator */
	private $validator;

	/** @var SchematiconNormalizer */
	private $normalizer;


	public function __construct()
	{
		$this->validator = new SchemaValidator();
		$this->normalizer = new SchematiconNormalizer();
	}


	public function normalize(array $apiSchema): array
	{
		$apiSchema = $this->normalizeEndpointLists($apiSchema);
		$apiSchema = $this->normalizeEndpointsSchemas($apiSchema);
		$apiSchema = $this->normalizeResourceSchemas($apiSchema);
		return $apiSchema;
	}


	private function normalizeEndpointLists(array $apiSchema): array
	{
		foreach ($apiSchema['sections'] as $i => $section) {
			$endpoints = [];
			foreach ($section['endpoints'] as $index => $value) {
				if (is_int($index)) {
					foreach ($value as $url => $endpoint) {
						$endpoints[$url] = $endpoint;
					}
				} else {
					$endpoints[$index] = $value;
				}
			}
			$apiSchema['sections'][$i]['endpoints'] = $endpoints;
		}
		return $apiSchema;
	}


	private function normalizeEndpointsSchemas(array $apiSchema): array
	{
		foreach ($apiSchema['sections'] as $i => $section) {
			foreach ($section['endpoints'] as $url => $generalEndpoint) {
				foreach (['put', 'get', 'post', 'delete', 'patch'] as $httpMethod) {
					if (!isset($generalEndpoint[$httpMethod])) {
						continue;
					}

					$endpoint = $generalEndpoint[$httpMethod];
					$normalizedEndpoint = $this->normalizeEndpointSchemas($apiSchema, $endpoint, $generalEndpoint, "[$url][$httpMethod]");
					$apiSchema['sections'][$i]['endpoints'][$url][$httpMethod] = $normalizedEndpoint;
				}
			}
		}
		return $apiSchema;
	}


	private function normalizeEndpointSchemas(array $apiSchema, array $endpoint, array $generalEndpoint, string $errorPath): array
	{
		// add wrappers
		foreach (['response_ok', 'response_error'] as $endpointPart) {
			if (isset($apiSchema[$endpointPart]['wrapper']) || isset($endpoint[$endpointPart]['wrapper'])) {
				$schema = array_key_exists('wrapper',
					$endpoint[$endpointPart]) ? $endpoint[$endpointPart]['wrapper'] : $apiSchema[$endpointPart]['wrapper'];
				if ($schema !== null) {
					array_walk_recursive($schema,
						function (& $value) use ($endpoint, $endpointPart) {
							if ($value === '@@') {
								$value = $endpoint[$endpointPart]['schema'] ?? ['type' => 'null'];
							}
						});
					$endpoint[$endpointPart]['schema'] = $endpoint[$endpointPart]['schema'] = $schema;
				}
			}
		}

		// normalize & validate schemas
		foreach (['request', 'response_ok', 'response_error'] as $endpointPart) {
			if (!isset($endpoint[$endpointPart]['schema'])) {
				continue;
			}

			$validationResult = $this->validator->validate($endpoint[$endpointPart]['schema']);
			if (!$validationResult->isValid()) {
				throw new \RuntimeException("Schema for $errorPath is not valid. " . implode("\n",
						$validationResult->getErrors()));
			}
			$endpoint[$endpointPart]['schema'] = $this->normalizer->normalize($endpoint[$endpointPart]['schema']);
		}

		// normalize params
		$parameters = array_merge_recursive($generalEndpoint['parameters'] ?? [], $endpoint['parameters'] ?? []);
		array_walk($parameters,
			function (& $value) {
				$value = $this->normalizer->normalize($value);
			});
		$endpoint['parameters'] = $parameters ?: null;

		return $endpoint;
	}


	private function normalizeResourceSchemas(array $apiSchema): array
	{
		$schemaValidator = new SchemaValidator();
		$normalizer = new SchematiconNormalizer();

		foreach ($apiSchema['resources'] ?? [] as $resourceName => $schema) {
			if (!$schemaValidator->validate($schema)->isValid()) {
				throw new \RuntimeException("Resource $resourceName is not valid schema");
			}
			$apiSchema['resources'][$resourceName] = $normalizer->normalize($schema);
		}

		return $apiSchema;
	}
}

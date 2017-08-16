<?php

/**
 * This file is part of the Schematicon library.
 * @license    MIT
 * @link       https://github.com/schematicon/api-validator
 */

namespace Schematicon\ApiValidator;

use Nette\Neon\Entity;
use Nette\Neon\Neon;
use Nette\Utils\Arrays;


class Loader
{
	/**
	 * @return mixed
	 */
	public function run(string $file)
	{
		$content = file_get_contents($file);
		$decoded = Neon::decode($content);
		return $this->processArrayValue($decoded, dirname($file));
	}


	private function processValue($value, string $basePath)
	{
		if ($value instanceof Entity) {
			return $this->processEntityValue($value, $basePath);
		} elseif (is_array($value)) {
			return $this->processArrayValue($value, $basePath);
		}
		return $value;
	}


	private function processArrayValue(array $array, string $basePath)
	{
		array_walk_recursive($array, function (& $value) use ($basePath) {
			if ($value instanceof Entity) {
				$value = $this->processEntityValue($value, $basePath);
			}
		});
		return $array;
	}


	private function processEntityValue(Entity $value, string $basePath)
	{
		// @include
		if ($value->value === '@include') {
			$value = $this->run($basePath . '/' . $value->attributes[0]);

		// @concat
		} elseif ($value->value === '@concat') {
			$concatenated = [];
			foreach ($value->attributes as $attribute) {
				$attribute = $this->processValue($attribute, $basePath);
				$concatenated = array_merge($concatenated, $attribute);
			}
			$value = $concatenated;

		// @merge
		} elseif ($value->value === '@merge') {
			$merged = [];
			foreach ($value->attributes as $attribute) {
				$attribute = $this->processValue($attribute, $basePath);
				$merged = Arrays::mergeTree($attribute, $merged);
			}
			$value = $merged;
		}

		return $value;
	}
}

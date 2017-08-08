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
		return $this->processArray($decoded, dirname($file));
	}


	private function processArray(array $array, string $basePath)
	{
		array_walk_recursive($array, function (& $value) use ($basePath) {
			if ($value instanceof Entity) {
				$value = $this->processEntity($value, $basePath);
			}
		});
		return $array;
	}


	private function processEntity(Entity $value, string $basePath)
	{
		// @include
		if ($value->value === '@include') {
			$value = $this->run($basePath . '/' . $value->attributes[0]);

		// @merge
		} elseif ($value->value === '@merge') {
			$merged = [];
			foreach ($value->attributes as $attribute) {
				if ($attribute instanceof Entity) {
					$attribute = $this->processEntity($attribute, $basePath);
				} elseif (is_array($attribute)) {
					$attribute = $this->processArray($attribute, $basePath);
				}
				$merged = Arrays::mergeTree($attribute, $merged);
			}
			$value = $merged;
		}

		return $value;
	}
}

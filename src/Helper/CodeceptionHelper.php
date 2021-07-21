<?php declare(strict_types = 1);

namespace WebChemistry\Codeception\Helper;

use Codeception\PHPUnit\TestCase;
use Codeception\TestInterface;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

final class CodeceptionHelper
{

	/**
	 * @param TestInterface $test
	 * @return array{ReflectionClass, ReflectionMethod}|null
	 */
	public static function getReflections(TestInterface $test): ?array
	{
		$object = self::getTestObject($test);
		if (!$object) {
			return null;
		}

		return [
			new ReflectionClass($object),
			new ReflectionMethod($object, self::getTestMethod($test)),
		];
	}

	public static function getTestMethod(TestInterface $test): string
	{
		if ($test instanceof TestCase) {
			return $test->getName();
		}

		return $test->getMetadata()->getName();
	}

	public static function getTestObject(TestInterface $test): ?object
	{
		if ($test instanceof TestCase) {
			return $test;
		}
		if (method_exists($test, 'getTestClass')) {
			return $test->getTestClass();
		}

		return null;
	}

	/**
	 * @return ReflectionProperty[]
	 */
	public static function getPropertiesByAttribute(TestInterface $test, string $attributeName): iterable
	{
		$reflections = self::getReflections($test);

		if (!$reflections) {
			return [];
		}

		foreach ($reflections[0]->getProperties() as $property) {
			if (!$property->isPublic()) {
				continue;
			}

			if (!$property->getAttributes($attributeName)) {
				continue;
			}

			yield $property;
		}
	}

	public static function hasAttribute(TestInterface $test, string $attributeName): bool
	{
		$reflections = self::getReflections($test);

		if (!$reflections) {
			return false;
		}

		[$reflectionClass, $reflectionMethod] = $reflections;

		return $reflectionClass->getAttributes($attributeName) || $reflectionMethod->getAttributes($attributeName);
	}

}

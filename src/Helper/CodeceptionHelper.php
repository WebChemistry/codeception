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
		return (bool) self::getAttribute($test, $attributeName);
	}

	/**
	 * @template T
	 * @param class-string<T> $attributeName
	 * @return T|null
	 */
	public static function getAttribute(TestInterface $test, string $attributeName): ?object
	{
		$reflections = self::getReflections($test);

		if (!$reflections) {
			return false;
		}

		[$reflectionClass, $reflectionMethod] = $reflections;


		$attributes = $reflectionMethod->getAttributes($attributeName);

		if (!$attributes) {
			$attributes = $reflectionClass->getAttributes($attributeName);
		}

		if (!$attributes) {
			return null;
		}

		return $attributes[0]->newInstance() ?? null;
	}

	public static function replacePathParameters(?string $path): ?string
	{
		if (!$path) {
			return $path;
		}

		return strtr($path, [
			'$dataDir' => rtrim(codecept_data_dir(), '/'),
			'$rootDir' => rtrim(codecept_root_dir(), '/'),
			'$testsDir' => realpath(rtrim(codecept_data_dir(), '/') . '/..'),
		]);
	}

}

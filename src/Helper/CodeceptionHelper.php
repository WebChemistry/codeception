<?php declare(strict_types = 1);

namespace WebChemistry\Codeception\Helper;

use Codeception\Exception\ModuleException;
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

	/**
	 * @template T of object
	 * @param string[] $classes
	 * @param class-string<T> $className
	 * @return T[]
	 */
	public static function loadClasses(string $moduleName, array $classes, string $className, ?string $optionName = null): array
	{
		$objects = [];

		foreach ($classes as $class) {
			if (!is_string($class)) {
				throw new ModuleException(
					$moduleName,
					$optionName ?
						sprintf('Option %s must be array of strings, %s given.', $optionName, get_debug_type($class)) :
						sprintf('Fixtures must be array of strings, %s given.', get_debug_type($class))
				);
			}

			if (!class_exists($class)) {
				throw new ModuleException($moduleName, sprintf('Class %s does not exist.', $class));
			}

			$object = new $class();

			if (!$object instanceof $className) {
				throw new ModuleException(
					$moduleName,
					sprintf('Class %s must be instance of %s.', get_debug_type($object), $className)
				);
			}

			$objects[] = $object;
		}

		return $objects;
	}

}

<?php declare(strict_types = 1);

namespace WebChemistry\Codeception\Module;

use Codeception\Module;
use Codeception\TestInterface;
use LogicException;
use Nette\DI\Attributes\Inject;
use Nette\DI\Container;
use Nette\Utils\FileSystem;
use ReflectionNamedType;
use WebChemistry\Codeception\Attribute\PurgeNetteTemp;
use WebChemistry\Codeception\Attribute\PurgeTemp;
use WebChemistry\Codeception\Helper\CodeceptionHelper;
use WebChemistry\Codeception\Module\Objects\CodeceptionBootingInterface;

final class NetteDIModule extends Module
{

	protected $config = [
		'class' => null,
	];

	protected $requiredFields = ['class'];

	private CodeceptionBootingInterface $booting;

	private Container $container;

	private CodeceptionConfiguration $configuration;

	public function _initialize()
	{
		$this->purge($this->getBooting()->getTempDir(), true);
	}

	public function _beforeSuite($settings = [])
	{
		foreach ($this->getBooting()->getDirectories() as $directory) {
			if ($directory->truncateAfterEachTest) {
				continue;
			}

			$this->purge($directory->path, true);
		}

		$this->purge($this->getBooting()->getTempDir(), true);
		$this->removeContainer();
	}

	public function _before(TestInterface $test)
	{
		foreach ($this->getBooting()->getDirectories() as $directory) {
			if (!$directory->truncateAfterEachTest) {
				continue;
			}

			$this->purge($directory->path, true);
		}

		if (CodeceptionHelper::getAttribute($test, PurgeNetteTemp::class)?->purge) {
			$this->purge($this->getBooting()->getTempDir(), true);
			$this->removeConfiguration();
		}

		$object = CodeceptionHelper::getTestObject($test);

		foreach (CodeceptionHelper::getPropertiesByAttribute($test, Inject::class) as $property) {
			$type = $property->getType();
			if (!$type instanceof ReflectionNamedType) {
				throw new LogicException(
					sprintf(
						'Property %s::%s must have named type.',
						$property->getName(),
						$property->getDeclaringClass()->getName()
					)
				);
			}
			if ($type->isBuiltin()) {
				throw new LogicException(
					sprintf(
						'Property %s::%s does not have built-in.',
						$property->getName(),
						$property->getDeclaringClass()->getName()
					)
				);
			}

			$property->setValue($object, $this->getContainer()->getByType($type->getName()));
		}
	}

	public function _afterSuite()
	{
		foreach ($this->getBooting()->getDirectories() as $directory) {
			$this->purge($directory->path, false);
		}

		$this->purge($this->getBooting()->getTempDir(), false);
		$this->removeContainer();
	}

	public function getDirectory(string $name, string $path): string
	{
		foreach ($this->getBooting()->getDirectories() as $directory) {
			if ($directory->name === $name) {
				return rtrim($directory->path, '/') . '/' . ltrim($path, '/');
			}
		}

		throw new LogicException(sprintf('Directory %s not found', $name));
	}

	public function getTempDir(): string
	{
		return $this->getBooting()->getTempDir();
	}

	public function getContainer(): Container
	{
		if (!$this->hasContainer()) {
			return $this->recreateContainer();
		}

		return $this->container;
	}

	private function purge(string $dir, bool $create): void
	{
		if (is_dir($dir)) {
			FileSystem::delete($dir);
		}

		if ($create) {
			FileSystem::createDir($dir);
		}
	}

	public function _recreateContainer(): Container
	{
		return $this->recreateContainer();
	}

	private function recreateContainer(): Container
	{
		if (session_status() === PHP_SESSION_ACTIVE) {
			session_write_close();
		}

		$this->removeContainer();

		return $this->container = $this->getBooting()->createContainer();
	}

	private function removeContainer(): void
	{
		unset($this->container);
	}

	private function hasContainer(): bool
	{
		return isset($this->container);
	}

	private function getBooting(): CodeceptionBootingInterface
	{
		if (!isset($this->booting)) {
			$booting = $this->config['class'];
			if (!is_string($booting)) {
				throw new LogicException('Option class must be a string.');
			}

			if (!class_exists($booting)) {
				throw new LogicException(sprintf('Factory class %s does not exist.', $booting));
			}

			$booting = new $booting();

			if (!$booting instanceof CodeceptionBootingInterface) {
				throw new LogicException(
					sprintf('Class %s must be instance of %s.', get_debug_type($booting), CodeceptionBootingInterface::class)
				);
			}

			$this->booting = $booting;
		}

		return $this->booting;
	}

}

<?php declare(strict_types = 1);

namespace WebChemistry\Codeception\Module;

use WebChemistry\Codeception\Attribute\PurgeTemp;
use WebChemistry\Codeception\Helper\CodeceptionHelper;
use Codeception\Lib\ModuleContainer;
use Codeception\Module;
use Codeception\TestInterface;
use LogicException;
use Nette\DI\Attributes\Inject;
use Nette\DI\Container;
use Nette\Utils\FileSystem;
use ReflectionNamedType;

final class NetteDIModule extends Module
{

	private Container $container;

	protected $config = [
		'factory' => '',
		'config' => null,
		'paths' => [],
	];

	protected $requiredFields = ['factory'];

	public function __construct(ModuleContainer $moduleContainer, $config = null)
	{
		parent::__construct($moduleContainer, $config);

		$dataDir = rtrim(codecept_data_dir(), '/');

		foreach ($this->config['paths'] as $i => $path) {
			if (!str_contains('%dataDir%', $path)) {
				throw new LogicException(sprintf('Parameter %%dataDir%% must contains path %s.', $path));
			}
			$this->config['paths'][$i] = strtr($path, [
				'%dataDir%' => $dataDir,
			]);
		}
	}

	public function _beforeSuite($settings = [])
	{
		$this->purgeTemp();
	}

	public function _before(TestInterface $test)
	{
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

		if (CodeceptionHelper::hasAttribute($test, PurgeTemp::class)) {
			$this->purgeTemp();
		}

		foreach ($this->config['paths'] as $path) {
			if (!is_dir($path)) {
				FileSystem::createDir($path);
			}
		}
	}

	public function _after(TestInterface $test): void
	{
		foreach ($this->config['paths'] as $path) {
			FileSystem::delete($path);
		}
	}

	public function _afterSuite()
	{
		$this->purgeTemp(false);
	}

	public function getTempDirectory(string $appendPath = ''): string
	{
		if ($appendPath) {
			$appendPath = '_temp/' . ltrim($appendPath, '/');
		} else {
			$appendPath = '_temp';
		}

		return codecept_data_dir($appendPath);
	}

	public function _recreateContainer(): Container
	{
		$config = $this->config['config'];
		if ($config) {
			$config = strtr($config, ['%rootDir%' => rtrim(codecept_root_dir(), '/')]);
		}

		return $this->container = ($this->config['factory'])($config, $this->getTempDirectory(), $this->config['paths']);
	}

	public function getContainer(): Container
	{
		if (!isset($this->container)) {
			$this->_recreateContainer();
		}

		return $this->container;
	}

//	public function replaceService(string $class, object $object): void
//	{
//		$names = $this->container->findByType($class);
//		foreach ($names as $name) {
//			if ($this->container->isCreated($name)) {
//				$this->container->removeService($name);
//			}
//
//			$this->container->addService($name, $object);
//		}
//	}

	private function removeContainer(): void
	{
		unset($this->container);
	}

	private function purgeTemp(bool $createTemp = true): void
	{
		if (is_dir($this->getTempDirectory())) {
			FileSystem::delete($this->getTempDirectory());
		}

		if ($createTemp) {
			FileSystem::createDir($this->getTempDirectory());
		}

		unset($this->container);
	}

}

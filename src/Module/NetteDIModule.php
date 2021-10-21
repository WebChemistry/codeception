<?php declare(strict_types = 1);

namespace WebChemistry\Codeception\Module;

use Nette\Schema\Helpers;
use WebChemistry\Codeception\Attribute\NetteTemp;
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
		'tempDir' => '$dataDir/_nette',
	];

	protected $requiredFields = ['factory', 'config'];

	private array $parameters = [];

	public function _beforeSuite($settings = [])
	{
		$this->_purgeTemp();
	}

	protected function validateConfig()
	{
		parent::validateConfig();

		$this->config['config'] = CodeceptionHelper::replacePathParameters($this->config['config']);
		$this->config['tempDir'] = CodeceptionHelper::replacePathParameters($this->config['tempDir']);

		$this->backupConfig['config'] = CodeceptionHelper::replacePathParameters($this->backupConfig['config']);
		$this->backupConfig['tempDir'] = CodeceptionHelper::replacePathParameters($this->backupConfig['tempDir']);
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

		if (CodeceptionHelper::getAttribute($test, NetteTemp::class)?->purge) {
			$this->_purgeTemp(true);
		}
	}

	public function _afterSuite()
	{
		$this->_purgeTemp(false);
	}

	public function _recreateContainer(): Container
	{
		$this->removeContainer();

		return $this->container = ($this->config['factory'])(
			$this->config['config'],
			$this->config['tempDir'],
			$this->parameters,
		);
	}

	public function _addParameters(array $parameters): void
	{
		$this->parameters = Helpers::merge($parameters, $this->parameters);
	}

	public function getContainer(): Container
	{
		if (!isset($this->container)) {
			$this->_recreateContainer();
		}

		return $this->container;
	}

	private function removeContainer(): void
	{
		if (session_status() === PHP_SESSION_ACTIVE) {
			session_write_close();
		}

		unset($this->container);
	}

	private function _purgeTemp(bool $createTemp = true): void
	{
		$dir = $this->config['tempDir'];
		if (is_dir($dir)) {
			FileSystem::delete($dir);
		}

		if ($createTemp) {
			FileSystem::createDir($dir);
		}

		unset($this->container);
	}

}

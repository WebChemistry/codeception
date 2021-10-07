<?php declare(strict_types = 1);

namespace WebChemistry\Codeception\Module;

use Codeception\Lib\ModuleContainer;
use Codeception\Module;
use Codeception\TestInterface;
use LogicException;
use Nette\Utils\FileSystem;
use WebChemistry\Codeception\Attribute\CodeceptionTemp;
use WebChemistry\Codeception\Helper\CodeceptionHelper;

final class TempModule extends Module
{

	protected $config = [
		'tempDir' => '$dataDir/_temp',
	];

	public function _initialize()
	{
		$this->moduleContainer->getModule(NetteDIModule::class)
			->_addParameters([
				'codeceptionTemp' => $this->config['tempDir'],
			]);
	}

	protected function validateConfig()
	{
		parent::validateConfig();

		$this->config['tempDir'] = CodeceptionHelper::replacePathParameters($this->config['tempDir']);

		$this->backupConfig['tempDir'] = CodeceptionHelper::replacePathParameters($this->backupConfig['tempDir']);
	}

	public function _beforeSuite($settings = [])
	{
		$this->_purgeTemp();
	}

	public function _before(TestInterface $test)
	{
		if (CodeceptionHelper::hasAttribute($test, CodeceptionTemp::class)) {
			$this->_purgeTemp(true);
		}
	}

	public function _afterSuite()
	{
		$this->_purgeTemp(false);
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
	}

	public function getTempDirectory(?string $append = null): string
	{
		$append = ltrim($append, '/');
		if (!$append) {
			return $this->config['tempDir'];
		}

		return $this->config['tempDir'] . '/' . $append;
	}

}

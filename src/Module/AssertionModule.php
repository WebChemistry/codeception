<?php declare(strict_types = 1);

namespace WebChemistry\Codeception\Module;

use Codeception\Module;
use FilesystemIterator;

final class AssertionModule extends Module
{

	public function assertFileCount(int $expected, string $path): void
	{
		$counter = new FilesystemIterator($path, FilesystemIterator::SKIP_DOTS);

		$this->assertCount($expected, $counter);
	}

}

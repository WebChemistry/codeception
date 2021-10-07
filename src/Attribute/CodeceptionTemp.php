<?php declare(strict_types = 1);

namespace WebChemistry\Codeception\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final class CodeceptionTemp
{

	/**
	 * @param bool $purge Purge temp before test
	 */
	public function __construct(
		public bool $purge = true,
	)
	{
	}

}

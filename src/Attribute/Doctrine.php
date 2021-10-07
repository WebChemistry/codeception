<?php declare(strict_types = 1);

namespace WebChemistry\Codeception\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS|Attribute::TARGET_METHOD)]
final class Doctrine
{

	/**
	 * @see https://codeception.com/docs/modules/Doctrine2#Description
	 * @param bool|null $transaction True - All doctrine queries will be wrapped in a transaction, which will be rolled back at the end of each test. Default true
	 * @param int|null $purgeMode 1 - DELETE, 2 - TRUNCATE, default DELETE
	 */
	public function __construct(
		public ?bool $transaction = null,
		public ?int $purgeMode = null,
	)
	{
	}

}

<?php declare(strict_types = 1);

namespace WebChemistry\Codeception\Helper;

final class CodeceptionDirectoriesHelper
{

	public static function getDataDir(): string
	{
		return rtrim(codecept_data_dir(), '/');
	}

	public static function getRootDir(): string
	{
		return rtrim(codecept_root_dir(), '/');
	}

	public static function replacePathParameters(?string $path): ?string
	{
		if (!$path) {
			return $path;
		}

		return strtr($path, [
			'$dataDir' => self::getDataDir(),
			'$rootDir' => self::getRootDir(),
		]);
	}

}

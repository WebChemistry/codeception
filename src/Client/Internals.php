<?php declare(strict_types = 1);

namespace WebChemistry\Codeception\Client;

use Nette\Application\IPresenter;
use Nette\Application\UI\Form;
use Nette\Application\UI\Presenter;

final class Internals
{

	public IPresenter $presenter;

	public ?string $redirection = null;

	/** @var mixed[] */
	public array $flashes = [];

	public bool $throwBadRequest = false;

	public bool $matchRoute = false;

	public ?string $error = null;

	public function getPresenter(): Presenter
	{
		assert($this->presenter instanceof Presenter);

		return $this->presenter;
	}

}

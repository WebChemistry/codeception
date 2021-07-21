<?php declare(strict_types = 1);

namespace WebChemistry\Codeception\Client;

use Nette\Application\IPresenter;
use Nette\Application\UI\Form;
use Nette\Application\UI\Presenter;

final class Internals
{

	public IPresenter $presenter;

	public Form $form;

	public bool $throwBadRequest = false;

	public function __construct(
		public bool $matchRoute = false,
	)
	{
	}

	public function getPresenter(): Presenter
	{
		assert($this->presenter instanceof Presenter);

		return $this->presenter;
	}

}

<?php declare(strict_types = 1);

namespace WebChemistry\Codeception\Module\Builder;

use Nette\Application\LinkGenerator;
use Nette\Application\Request;
use Nette\Application\UI\Form;
use Nette\Application\UI\Presenter;
use Nette\ComponentModel\IComponent;
use Nette\DI\Container;
use Nette\Forms\SubmitterControl;
use Nette\Http\FileUpload;
use WebChemistry\Codeception\Argument\Page;
use WebChemistry\Codeception\Argument\RoutePage;
use WebChemistry\Codeception\Client\Internals;
use WebChemistry\Codeception\Client\NetteClient;
use WebChemistry\Codeception\Exceptions\CodeceptionInternalException;
use WebChemistry\Codeception\Module\Assert\FormAsserts;

final class NetteFormBuilder
{

	private string $submitter;

	/** @var FileUpload[] */
	private array $files = [];

	private string $method;

	/** @var mixed[] */
	private array $parameters = [];

	private bool $defaultFromRequest = false;

	/** @var mixed[] */
	private array $defaultParameters = [];

	public function __construct(
		private NetteClient $client,
		private Container $container,
		private string|RoutePage $page,
		private string $componentName,

	)
	{
	}

	public function setMethod(string $method): self
	{
		$this->method = $method;

		return $this;
	}

	public function setDefaultFromRequest(bool $defaultFromRequest = true): self
	{
		$this->defaultFromRequest = $defaultFromRequest;

		return $this;
	}

	/**
	 * @param FileUpload[] $files
	 */
	public function setFiles(array $files): self
	{
		$this->files = $files;

		return $this;
	}

	/**
	 * @param mixed[] $parameters
	 */
	public function setParameters(array $parameters): self
	{
		$this->parameters = $parameters;

		return $this;
	}

	/**
	 * @param mixed[] $defaultParameters
	 */
	public function setDefaultParameters(array $defaultParameters): self
	{
		$this->defaultParameters = $defaultParameters;

		return $this;
	}

	public function setSubmitter(string $name): self
	{
		$this->submitter = $name;

		return $this;
	}

	public function send(): FormAsserts
	{
		$this->method ??= 'POST';

		$this->client->onClonedPresenter[] = function (Presenter $presenter, Request $request): void {
			if ($this->defaultFromRequest) {
				$presenter->run(new Request($request->getPresenterName(), params: $request->getParameters()));

				$form = $presenter->getComponent($this->componentName, false);

				if (!$form) {
					throw new CodeceptionInternalException(sprintf('Form %s does not exist in presenter %s.', $this->componentName, $presenter::class));
				}

				if (!$form instanceof Form) {
					throw new CodeceptionInternalException(sprintf('Given component %s is not a form.', $form::class));
				}

				$this->defaultParameters = array_merge($this->defaultParameters, $form->getValues('array'));
			}
		};

		$this->client->onBeforeRequest[] = function (Presenter $presenter, Request $request): void {
			$this->parameters = array_merge($this->defaultParameters, $this->parameters);
			$this->parameters['_' . Presenter::SIGNAL_KEY] = $this->componentName . IComponent::NAME_SEPARATOR . 'submit';

			if (isset($this->submitter)) {
				$this->parameters[$this->submitter] = 'click';
			}

			array_walk_recursive($this->parameters, static function (&$value) {
				$value = (string) $value;
			});

			$request->setMethod($this->method);
			$request->setFiles($this->files);
			$request->setPost($this->parameters);
		};

		if ($this->page instanceof RoutePage) {
			$this->page = $this->container->getByType(LinkGenerator::class)->link($this->page->destination, $this->page->parameters);
		}

		$internals = null;
		$this->client->onResponse[] = function () use (&$internals): void {
			$internals = $this->client->internals;
		};

		$this->client->request($this->method, $this->page);

		if (!$internals instanceof Internals) {
			throw new CodeceptionInternalException('Something gone wrong.');
		}

		$presenter = $internals->presenter;

		$form = $presenter->getComponent($this->componentName, false);

		if (!$form) {
			throw new CodeceptionInternalException(sprintf('Form %s does not exist in presenter %s.', $this->componentName, $presenter::class));
		}

		if (!$form instanceof Form) {
			throw new CodeceptionInternalException(sprintf('Given component %s is not a form.', $form::class));
		}

		if (isset($this->submitter)) {
			$submit = $form->getComponent($this->submitter, false);

			if (!$submit) {
				throw new CodeceptionInternalException(
					sprintf('Submitter %s does not exist in %s.', $this->submitter, $this->componentName)
				);
			}

			if (!$submit instanceof SubmitterControl) {
				throw new CodeceptionInternalException(sprintf('Given submitter %s is not submit button.', $this->submitter));
			}
		}

		return new FormAsserts($form, $internals);
	}

}

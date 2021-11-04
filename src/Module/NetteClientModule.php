<?php declare(strict_types = 1);

namespace WebChemistry\Codeception\Module;

use Nette\Forms\Controls\BaseControl;
use WebChemistry\Codeception\Client\Internals;
use WebChemistry\Codeception\Client\NetteClient;
use Codeception\Lib\Framework;
use Codeception\TestInterface;
use LogicException;
use Nette\Application\LinkGenerator;
use Nette\Application\Request;
use Nette\Application\UI\Form;
use Nette\Application\UI\Presenter;
use Nette\ComponentModel\IComponent;
use Nette\Forms\Controls\SubmitButton;
use Nette\Http\UrlScript;
use PHPUnit\Framework\Assert;
use Symfony\Component\BrowserKit\Response;

/**
 * @property NetteClient $client
 */
final class NetteClientModule extends Framework
{

	private bool $debugMode = false;

	private string $scriptPath = '/';

	private Form $submittedForm;

	public function _before(TestInterface $test): void
	{
		$this->client = new NetteClient(
			$this->getDIModule()->_recreateContainer(),
			$this->config['url'] ?? 'http://localhost',
		);
	}

	private function trimBaseUrl(string $url): string
	{
		if (!str_starts_with($url, 'http')) {
			return $url;
		}

		$urlScript = new UrlScript($url, $this->scriptPath);

		return '/' . ltrim(substr($url, strlen($urlScript->getBaseUrl())), '/');
	}

	public function amOnRoute(string $dest, array $parameters = []): void
	{
		$container = $this->getDIModule()->getContainer();

		$this->amOnPage($container->getByType(LinkGenerator::class)->link($dest, $parameters));
	}

	public function amOnPage($page)
	{
		parent::amOnPage($this->trimBaseUrl($page));
	}

	public function seeNetteBadRequest(): void
	{
		Assert::assertTrue($this->getNetteInternals()->throwBadRequest, 'Bad request is not threw.');
	}

	public function sendNetteForm(
		string $page,
		string $componentName,
		array $params,
		array $files = [],
		?string $button = null,
		?callable $onRequest = null,
	): void
	{
		if (!str_ends_with($componentName, '-form')) {
			$componentName .= '-form';
		}

		$this->client->onRequest[] = function (Presenter $presenter, Request $request) use ($componentName, $params, $files, $button, $onRequest): void {
			$params['_' . Presenter::SIGNAL_KEY] = $componentName . IComponent::NAME_SEPARATOR . 'submit';

			if ($button) {
				$params[$button] = 'click';
			}

			$request->setMethod('POST');
			$request->setFiles($files);
			$request->setPost($params);

			if ($onRequest) {
				$onRequest($presenter[$componentName]);
			}
		};

		$this->client->onResponse[] = function (Presenter $presenter) use ($componentName): void {
			$this->submittedForm = $presenter[$componentName];
		};

		$this->crawler = $this->clientRequest('POST', $this->trimBaseUrl($page));
	}

	public function wasNetteFormSuccess(): void
	{
		if (!isset($this->submittedForm)) {
			$this->fail('Form was not correctly requested.');

			return;
		}

		$form = $this->submittedForm;

		if (!$form->isSubmitted()) {
			$this->fail('Form was not submitted.');

			return;
		}

		if ($form->isSuccess()) {
			return;
		}

		if ($form->hasErrors()) {
			$this->fail("Form has these errors: \n" . $this->printFormErrors($form));

			return;
		}

		$this->fail('Form was not success.');
	}

	private function printFormErrors(Form $form): string
	{
		$errors = [];

		foreach ($form->getControls() as $control) {
			if (!$control instanceof BaseControl) {
				continue;
			}

			if (!$control->hasErrors()) {
				continue;
			}

			foreach ($control->getErrors() as $error) {
				$errors[] = $control->getName()  . ': ' . $error;
			}
		}

		foreach ($form->getOwnErrors() as $error) {
			$errors[] = 'Form error: ' . $error;
		}

		return implode("\n", $errors);
 	}

	public function hadNetteFormValues(array $values): void
	{
		if (!isset($this->submittedForm)) {
			$this->fail('Form was not correctly requested.');
		}

		$this->assertSame($values, $this->submittedForm);
	}

	public function submitNetteForm(string $componentName, array $params, $button = null): void
	{
		if (!isset($this->client->internals)) {
			throw new LogicException('First send request to a page.');
		}

		if (!str_ends_with($componentName, '-form')) {
			$componentName .= '-form';
		}

		/** @var Form $form */
		$form = $this->client->internals->getPresenter()[$componentName];
		$this->assertInstanceOf(Form::class, $form, 'Submitted nette form must be instance of ' . Form::class);

		$this->assertNotNull($form->getElementPrototype()->id, 'The form has not id.');

		if ($button === null) {
			foreach ($form->getControls() as $control) {
				if ($control instanceof SubmitButton) {
					$button = $control;
					break;
				}
			}

			$this->assertNotNull($button, 'The form has not submit button.');
			$this->assertNotNull($button->getName(), 'The submit button has not name.');
		}

		$this->client->onResponse[] = function (Presenter $presenter) use ($componentName): void {
			$this->submittedForm = $presenter[$componentName];
		};

		$this->submitForm('#' . $form->getElementPrototype()->id, $params, $button->getName());
	}

	public function getNetteInternals(): Internals
	{
		return $this->client->internals;
	}

	public function getLastResponse(): Response
	{
		return $this->client->lastResponse;
	}

	public function enableDebugMode(): void
	{
		$this->client->debugMode = true;
		$this->debugMode = true;
	}

	private function getDIModule(): NetteDIModule
	{
		$module = $this->getModule(NetteDIModule::class);
		assert($module instanceof NetteDIModule);

		return $module;
	}

}

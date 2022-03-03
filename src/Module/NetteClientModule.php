<?php declare(strict_types = 1);

namespace WebChemistry\Codeception\Module;

use Codeception\Lib\Framework;
use Codeception\TestInterface;
use LogicException;
use Nette\Application\LinkGenerator;
use Nette\Application\Request;
use Nette\Application\UI\Form;
use Nette\Application\UI\Presenter;
use Nette\ComponentModel\IComponent;
use Nette\Forms\Controls\BaseControl;
use Nette\Forms\Controls\SubmitButton;
use Nette\Http\UrlScript;
use PHPUnit\Framework\Assert;
use Symfony\Component\BrowserKit\Response;
use WebChemistry\Codeception\Argument\Page;
use WebChemistry\Codeception\Argument\RoutePage;
use WebChemistry\Codeception\Client\Internals;
use WebChemistry\Codeception\Client\NetteClient;
use WebChemistry\Codeception\Module\Builder\NetteFormBuilder;

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

	public function buildFormRequest($page, string $componentName): NetteFormBuilder
	{
		return new NetteFormBuilder($this->client, $this->getDIModule()->getContainer(), $page, $componentName);
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

<?php declare(strict_types = 1);

namespace WebChemistry\Codeception\Client;

use LogicException;
use Nette\Application\Application;
use Nette\Application\BadRequestException;
use Nette\Application\InvalidPresenterException;
use Nette\Application\IPresenter;
use Nette\Application\IPresenterFactory;
use Nette\Application\Responses\RedirectResponse;
use Nette\Application\Responses\TextResponse;
use Nette\Application\UI\Presenter;
use Nette\Bridges\ApplicationLatte\Template;
use Nette\DI\Container;
use Nette\Http\UrlScript;
use Nette\Routing\Router;
use Nette\Utils\Strings;
use ReflectionClass;
use ReflectionProperty;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\BrowserKit\CookieJar;
use Symfony\Component\BrowserKit\History;
use Symfony\Component\BrowserKit\Request;
use Symfony\Component\BrowserKit\Response;
use WebChemistry\Codeception\Exceptions\InvalidSignalReceivedException;

final class NetteClient extends AbstractBrowser
{

	public bool $debugMode = false;

	/** @var callable[] */
	public array $onRequest = [];

	/** @var callable[] */
	public array $onResponse = [];

	public Response $lastResponse;

	public Internals $internals;

	private string $scriptPath = '/';

	/**
	 * @param array $server The server parameters (equivalent of $_SERVER)
	 */
	public function __construct(
		private Container $container,
		private string $url,
		array $server = [],
		History $history = null,
		CookieJar $cookieJar = null,
	)
	{
		parent::__construct($server, $history, $cookieJar);
	}

	/**
	 * @param Request $request
	 */
	protected function doRequest($request): Response
	{
		$router = $this->container->getByType(Router::class);
		$params = $router->match($netteRequest = $this->createRequest($request));

		$this->internals = new Internals();

		if (!$params) {
			return $this->lastResponse = new Response(sprintf('No route for HTTP request (%s).', (string) $netteRequest->getUrl()), 404);
		}

		$this->internals->matchRoute = true;

		$presenter = $params['presenter'] ?? null;
		if (!is_string($presenter)) {
			return $this->lastResponse = new Response('Missing presenter in route definition.', 500);
		}

		if (Strings::startsWith($presenter, 'Nette:') && $presenter !== 'Nette:Micro') {
			return $this->lastResponse = new Response('Invalid request. Presenter is not achievable.', 404);
		}

		$applicationRequest = new \Nette\Application\Request(
			$presenter,
			$request->getMethod(),
			$params,
			$request->getParameters(),
			$request->getFiles(),
		);

		$presenterFactory = $this->container->getByType(IPresenterFactory::class);
		try {
			$this->internals->presenter = $presenter = $presenterFactory->createPresenter($applicationRequest->getPresenterName());
		} catch (InvalidPresenterException $e) {
			if ($this->debugMode) {
				throw $e;
			}

			return $this->lastResponse = new Response($e->getMessage(), 404);
		}

		$this->applyToApplication($applicationRequest, $presenter);

		if ($presenter instanceof Presenter) {
			$presenter->autoCanonicalize = false;
		}

		foreach ($this->onRequest as $callback) {
			$callback($presenter, $applicationRequest);
		}

		$this->onRequest = [];

		$this->setSameSite($presenter);

		try {
			$response = $presenter->run($applicationRequest);
		} catch (BadRequestException $e) {
			if ($this->debugMode) {
				throw $e;
			}

			$this->internals->throwBadRequest = true;

			if ($e->getCode() === 403 && str_starts_with($e->getMessage(), 'The signal receiver component')) {
				throw new InvalidSignalReceivedException($e->getMessage(), previous: $e);
			}

			return $this->lastResponse = new Response($e->getMessage(), 404);
		}

		foreach ($this->onResponse as $callback) {
			$callback($presenter, $response);
		}

		$this->onResponse = [];

		return $this->lastResponse = $this->createResponse($response, $presenter);
	}

	private function createRequest(Request $request): \Nette\Http\Request
	{
		$uri = str_replace('http://localhost', trim($this->url, '/'), $request->getUri());

		return new \Nette\Http\Request(
			new UrlScript($uri, $this->scriptPath),
		);
	}

	private function applyToApplication(\Nette\Application\Request $request, IPresenter $presenter): void
	{
		$application = $this->container->getByType(Application::class);
		$applicationReflection = new ReflectionClass($application);

		$requests = $applicationReflection->getProperty('requests');
		$requests->setAccessible(true);
		$requests->setValue($application, [$request]);

		$presenterProperty = $applicationReflection->getProperty('presenter');
		$presenterProperty->setAccessible(true);
		$presenterProperty->setValue($application, $presenter);
	}

	private function createResponse(\Nette\Application\Response $response, IPresenter $presenter)
	{
		$netteResponse = $presenter instanceof Presenter ? $presenter->getHttpResponse() : null;
		$code = $netteResponse?->getCode() ?? 200;
		$headers = $netteResponse?->getHeaders() ?? [];

		if ($response instanceof TextResponse) {
			$source = $response->getSource();

			if ($source instanceof Template) {
				$source = (string) $source;
			} elseif (!is_string($source)) {
				throw new LogicException(
					sprintf(
						'Response source must be instance of %s or a string, %s given',
						Template::class,
						$source::class
					)
				);
			}

			return new Response($source, $code, $headers);
		} elseif ($response instanceof RedirectResponse) {
			$headers['Location'] = $response->getUrl();

			return new Response('', $response->getCode(), $headers);
		} else {
			throw new LogicException(
				sprintf('Response returned from presenter %s is not supported.', $response::class)
			);
		}
	}

	private function setSameSite(IPresenter $presenter): void
	{
		if (!$presenter instanceof Presenter) {
			return;
		}

		$reflection = new ReflectionProperty(Presenter::class, 'httpRequest');
		$reflection->setAccessible(true);
		$reflection->setValue($presenter, new \WebChemistry\Codeception\Stub\Request($presenter->getHttpRequest()));

	}

}

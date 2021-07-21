<?php declare(strict_types = 1);

namespace WebChemistry\Codeception\Stub;

use Nette\Http\IRequest;
use Nette\Http\UrlImmutable;
use Nette\Http\UrlScript;

final class Request implements IRequest
{

	public function __construct(private \Nette\Http\Request $request)
	{
	}

	public function isSameSite(): bool
	{
		return true;
	}

	public function getReferer(): ?UrlImmutable
	{
		return $this->request->getReferer();
	}

	public function getUrl(): UrlScript
	{
		return $this->request->getUrl();
	}

	public function getQuery(string $key = null)
	{
		return $this->request->getQuery($key);
	}

	public function getPost(string $key = null)
	{
		return $this->request->getPost($key);
	}

	public function getFile(string $key)
	{
		return $this->request->getFile($key);
	}

	public function getFiles(): array
	{
		return $this->request->getFiles();
	}

	public function getCookie(string $key)
	{
		return $this->request->getCookie($key);
	}

	public function getCookies(): array
	{
		return $this->request->getCookies();
	}

	public function getMethod(): string
	{
		return $this->request->getMethod();
	}

	public function isMethod(string $method): bool
	{
		return $this->request->isMethod($method);
	}

	public function getHeader(string $header): ?string
	{
		return $this->request->getHeader($header);
	}

	public function getHeaders(): array
	{
		return $this->request->getHeaders();
	}

	public function isSecured(): bool
	{
		return $this->request->isSecured();
	}

	public function isAjax(): bool
	{
		return $this->request->isAjax();
	}

	public function getRemoteAddress(): ?string
	{
		return $this->request->getRemoteAddress();
	}

	public function getRemoteHost(): ?string
	{
		return $this->request->getRemoteHost();
	}

	public function getRawBody(): ?string
	{
		return $this->request->getRawBody();
	}

}

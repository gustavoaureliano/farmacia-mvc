<?php

namespace App\Controllers;

use App\Views\MainView;

class Controller
{
	protected array $segments;
	protected array $request;
	protected string $rawBody;
	private array $params = [];

	public function __construct(array $segments = [])
	{
		$this->segments = $segments;
		$this->request = $_REQUEST;
		$this->rawBody = file_get_contents('php://input') ?: '';
	}

	public function executar(): void
	{
	}

	protected function render(string $viewName): void
	{
		$view = new MainView($viewName);
		$view->render($this->params);
	}

	protected function addParam(string $key, mixed $value): void
	{
		$this->params[$key] = $value;
	}

	protected function redirect(string $path): void
	{
		header('Location: ' . $path);
		exit;
	}

	protected function baseUrl(): string
	{
		return (string) ($GLOBALS['BASE_URL'] ?? '');
	}

	protected function url(string $path): string
	{
		$base = $this->baseUrl();
		if ($base === '') {
			return $path;
		}

		if ($path === '' || $path[0] !== '/') {
			return $path;
		}

		return $base . $path;
	}
}

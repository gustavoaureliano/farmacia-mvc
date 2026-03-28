<?php

namespace App;

class Application
{
	public function executar(): void
	{
		$rawUrl = $_GET['url'] ?? '';
		$segments = $this->parseUrl($rawUrl);

		$controllerSegment = $segments[0] ?? 'home';
		$controllerClass = ucfirst($controllerSegment) . 'Controller';
		$controllerFqcn = 'App\\Controllers\\' . $controllerClass;

		if (!class_exists($controllerFqcn)) {
			http_response_code(404);
			$controllerFqcn = 'App\\Controllers\\HomeController';
			$segments = ['home'];
		}

		$controller = new $controllerFqcn($segments);
		$controller->executar();
	}

	private function parseUrl(string $url): array
	{
		$url = trim($url);

		if ($url === '') {
			return ['home'];
		}

		$clean = trim($url, '/');

		if ($clean === '') {
			return ['home'];
		}

		return array_values(array_filter(explode('/', $clean), static function ($part) {
			return $part !== '';
		}));
	}
}

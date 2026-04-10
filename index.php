<?php

declare(strict_types=1);

// Base URL (supports hosting in a subfolder, e.g. /farmacia-mvc-1)
$scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
$baseDir = dirname($scriptName);
if ($baseDir === '/' || $baseDir === '\\' || $baseDir === '.' || $baseDir === '') {
	$baseUrl = '';
} else {
	$baseUrl = rtrim($baseDir, '/');
}
$GLOBALS['BASE_URL'] = $baseUrl;

if (!isset($_GET['url']) || (string) $_GET['url'] === '') {
	$pathInfo = $_SERVER['PATH_INFO'] ?? '';
	if (is_string($pathInfo) && $pathInfo !== '' && $pathInfo !== '/') {
		$_GET['url'] = ltrim($pathInfo, '/');
	} else {
		$requestUri = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/');
		$requestUri = str_replace('\\', '/', $requestUri);

		$path = $requestUri;
		if ($baseUrl !== '' && str_starts_with($path, $baseUrl)) {
			$path = substr($path, strlen($baseUrl));
		}

		$path = '/' . ltrim($path, '/');

		if (str_starts_with($path, '/index.php/')) {
			$path = substr($path, strlen('/index.php'));
		} elseif ($path === '/index.php') {
			$path = '/';
		}

		$_GET['url'] = ltrim($path, '/');
	}
}

session_start();

require_once __DIR__ . '/vendor/autoload.php';

use App\Application;

$app = new Application();
$app->executar();

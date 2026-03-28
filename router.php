<?php

declare(strict_types=1);

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$file = __DIR__ . $uri;

if ($uri !== '/' && file_exists($file) && is_file($file)) {
	return false;
}

$_GET['url'] = ltrim($uri, '/');

require __DIR__ . '/index.php';

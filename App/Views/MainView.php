<?php

namespace App\Views;

class MainView
{
	private string $fileName;

	public function __construct(string $fileName)
	{
		$this->fileName = $fileName;
	}

	public function render(array $params = []): void
	{
		extract($params, EXTR_SKIP);
		$viewFile = __DIR__ . '/pages/' . $this->fileName . '.php';

		if (!file_exists($viewFile)) {
			http_response_code(500);
			echo 'View not found: ' . htmlspecialchars($this->fileName, ENT_QUOTES, 'UTF-8');
			return;
		}

		require __DIR__ . '/pages/_layout_header.php';
		require $viewFile;
		require __DIR__ . '/pages/_layout_footer.php';
	}
}

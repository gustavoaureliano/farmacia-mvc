<?php

namespace App\Controllers;

class HomeController extends Controller
{
	public function executar(): void
	{
		$this->render('home');
	}
}

<?php

namespace App\Controllers;

use App\DAO\FuncionarioDAO;

class FuncionariosController extends Controller
{
	private FuncionarioDAO $funcionarioDAO;

	public function __construct(array $segments = [])
	{
		parent::__construct($segments);
		$this->funcionarioDAO = new FuncionarioDAO();
	}

	public function executar(): void
	{
		$action = $this->segments[1] ?? 'listar';

		if ($action === 'novo') {
			$this->render('funcionarios/novo');
			return;
		}

		if ($action === 'salvar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
			$this->salvar();
			return;
		}

		$this->addParam('funcionarios', $this->funcionarioDAO->listar());
		$this->render('funcionarios/listar');
	}

	private function salvar(): void
	{
		$cargo = (string) ($this->request['cargo'] ?? 'atendente');
		if (!in_array($cargo, ['farmaceutico', 'atendente'], true)) {
			$cargo = 'atendente';
		}

		$data = [
			'nome' => trim((string) ($this->request['nome'] ?? '')),
			'cargo' => $cargo,
			'cpf' => preg_replace('/\D+/', '', (string) ($this->request['cpf'] ?? '')),
			'crf' => trim((string) ($this->request['crf'] ?? '')),
		];

		if ($data['nome'] === '' || strlen($data['cpf']) !== 11) {
			$_SESSION['flash_error'] = 'Informe nome e CPF valido com 11 digitos.';
			$this->redirect($this->url('/funcionarios/novo'));
		}

		if ($data['cargo'] === 'farmaceutico' && $data['crf'] === '') {
			$_SESSION['flash_error'] = 'Farmaceutico exige CRF.';
			$this->redirect($this->url('/funcionarios/novo'));
		}

		$this->funcionarioDAO->criar($data);
		$_SESSION['flash_success'] = 'Funcionario cadastrado com sucesso.';
		$this->redirect($this->url('/funcionarios'));
	}
}

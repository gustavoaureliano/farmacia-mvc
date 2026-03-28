<?php

namespace App\Controllers;

use App\DAO\ClienteDAO;

class ClientesController extends Controller
{
	private ClienteDAO $clienteDAO;

	public function __construct(array $segments = [])
	{
		parent::__construct($segments);
		$this->clienteDAO = new ClienteDAO();
	}

	public function executar(): void
	{
		$action = $this->segments[1] ?? 'listar';

		if ($action === 'novo') {
			$this->render('clientes/novo');
			return;
		}

		if ($action === 'salvar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
			$this->salvar();
			return;
		}

		$this->addParam('clientes', $this->clienteDAO->listar());
		$this->render('clientes/listar');
	}

	private function salvar(): void
	{
		$data = [
			'nome' => trim((string) ($this->request['nome'] ?? '')),
			'cpf' => preg_replace('/\D+/', '', (string) ($this->request['cpf'] ?? '')),
			'data_nascimento' => trim((string) ($this->request['data_nascimento'] ?? '')),
			'telefone' => trim((string) ($this->request['telefone'] ?? '')),
		];

		if ($data['nome'] === '' || strlen($data['cpf']) !== 11) {
			$_SESSION['flash_error'] = 'Informe nome e CPF valido com 11 digitos.';
			$this->redirect('/clientes/novo');
		}

		$this->clienteDAO->criar($data);
		$_SESSION['flash_success'] = 'Cliente cadastrado com sucesso.';
		$this->redirect('/clientes');
	}
}

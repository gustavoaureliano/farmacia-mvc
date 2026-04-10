<?php

namespace App\Controllers;

use App\DAO\ClienteDAO;
use DomainException;

class ClientesController extends Controller
{
	private ClienteDAO $clienteDAO;

	private function baseUrl(): string
	{
		return (string) ($GLOBALS['BASE_URL'] ?? '');
	}

	private function url(string $path): string
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

		if ($action === 'editar') {
			$this->editar();
			return;
		}

		if ($action === 'atualizar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
			$this->atualizar();
			return;
		}

		if ($action === 'excluir' && $_SERVER['REQUEST_METHOD'] === 'POST') {
			$this->excluir();
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
			$this->redirect($this->url('/clientes/novo'));
		}

		try {
			$this->clienteDAO->criar($data);
			$_SESSION['flash_success'] = 'Cliente cadastrado com sucesso.';
			$this->redirect($this->url('/clientes'));
		} catch (\Throwable $e) {
			$_SESSION['flash_error'] = 'Falha ao cadastrar cliente: ' . $e->getMessage();
			$this->redirect($this->url('/clientes/novo'));
		}
	}

	private function editar(): void
	{
		$cpf = (string) ($this->request['cpf'] ?? '');
		$returnTo = (string) ($this->request['return_to'] ?? $this->url('/clientes'));

		$cliente = $this->clienteDAO->buscarPorCpf($cpf);
		if ($cliente === null) {
			$_SESSION['flash_error'] = 'Cliente nao encontrado.';
			$this->redirect($this->url('/clientes'));
		}

		$this->addParam('cliente', $cliente);
		$this->addParam('returnTo', $returnTo);
		$this->render('clientes/editar');
	}

	private function atualizar(): void
	{
		$cpfOriginal = (string) ($this->request['cpf_original'] ?? '');
		$cpfNovo = (string) ($this->request['cpf_novo'] ?? '');
		$returnTo = trim((string) ($this->request['return_to'] ?? ''));
		if ($returnTo === '') {
			$returnTo = $this->url('/clientes');
		}

		$data = [
			'nome' => trim((string) ($this->request['nome'] ?? '')),
			'data_nascimento' => trim((string) ($this->request['data_nascimento'] ?? '')),
			'telefone' => trim((string) ($this->request['telefone'] ?? '')),
			'cpf_novo' => $cpfNovo,
		];

		if ($data['nome'] === '') {
			$_SESSION['flash_error'] = 'Informe um nome valido.';
			$this->redirect($this->url('/clientes/editar?cpf=' . urlencode($cpfOriginal) . '&return_to=' . urlencode($returnTo)));
		}

		try {
			$this->clienteDAO->atualizar($cpfOriginal, $data);
			$_SESSION['flash_success'] = 'Cliente atualizado com sucesso.';
			$this->redirect($returnTo);
		} catch (DomainException $e) {
			$_SESSION['flash_error'] = $e->getMessage();
			$this->redirect($this->url('/clientes/editar?cpf=' . urlencode($cpfOriginal) . '&return_to=' . urlencode($returnTo)));
		} catch (\Throwable $e) {
			$_SESSION['flash_error'] = 'Falha ao atualizar cliente: ' . $e->getMessage();
			$this->redirect($this->url('/clientes/editar?cpf=' . urlencode($cpfOriginal) . '&return_to=' . urlencode($returnTo)));
		}
	}

	private function excluir(): void
	{
		$cpf = (string) ($this->request['cpf'] ?? '');

		try {
			$this->clienteDAO->excluir($cpf);
			$_SESSION['flash_success'] = 'Cliente excluido com sucesso.';
			$this->redirect($this->url('/clientes'));
		} catch (DomainException $e) {
			$_SESSION['flash_error'] = $e->getMessage();
			$this->redirect($this->url('/clientes'));
		} catch (\Throwable $e) {
			$_SESSION['flash_error'] = 'Falha ao excluir cliente: ' . $e->getMessage();
			$this->redirect($this->url('/clientes'));
		}
	}
}

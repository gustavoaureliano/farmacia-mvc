<?php

namespace App\Controllers;

use App\DAO\ClienteDAO;
use App\DAO\ProdutoDAO;
use App\DAO\ReceitaDAO;
use Throwable;

class ReceitasController extends Controller
{
	private ReceitaDAO $receitaDAO;
	private ClienteDAO $clienteDAO;
	private ProdutoDAO $produtoDAO;

	public function __construct(array $segments = [])
	{
		parent::__construct($segments);
		$this->receitaDAO = new ReceitaDAO();
		$this->clienteDAO = new ClienteDAO();
		$this->produtoDAO = new ProdutoDAO();
	}

	public function executar(): void
	{
		$action = $this->segments[1] ?? 'listar';

		if ($action === 'novo') {
			$this->novo();
			return;
		}

		if ($action === 'salvar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
			$this->salvar();
			return;
		}

		$this->listar();
	}

	private function listar(): void
	{
		$this->addParam('receitas', $this->receitaDAO->listarComCliente());
		$this->render('receitas/listar');
	}

	private function novo(): void
	{
		$clienteId = trim((string) ($this->request['cliente_id'] ?? ''));
		$produtoId = trim((string) ($this->request['produto_id'] ?? ''));
		$returnTo = $this->sanitizeInternalPath((string) ($this->request['return_to'] ?? ''));

		$this->addParam('clientes', $this->clienteDAO->listar());
		$this->addParam('produtos', $this->produtoDAO->listar());
		$this->addParam('clienteIdSelecionado', $clienteId !== '' ? $clienteId : null);
		$this->addParam('produtoIdSelecionado', $produtoId !== '' ? $produtoId : null);
		$this->addParam('returnTo', $returnTo);
		$this->render('receitas/novo');
	}

	private function salvar(): void
	{
		$returnTo = $this->sanitizeInternalPath((string) ($this->request['return_to'] ?? ''));
		$clienteId = trim((string) ($this->request['cliente_id'] ?? ''));
		$produtoId = trim((string) ($this->request['produto_id'] ?? ''));

		$data = [
			'cliente_id' => $clienteId,
			'medico_nome' => trim((string) ($this->request['medico_nome'] ?? '')),
			'crm' => trim((string) ($this->request['crm'] ?? '')),
			'data_receita' => trim((string) ($this->request['data_receita'] ?? '')),
			'observacoes' => trim((string) ($this->request['observacoes'] ?? '')),
		];

		$posologia = trim((string) ($this->request['posologia'] ?? ''));

		if ($data['cliente_id'] === '' || $data['medico_nome'] === '' || $data['crm'] === '' || $data['data_receita'] === '' || $produtoId === '') {
			$_SESSION['flash_error'] = 'Informe cliente, medico, CRM, data e produto da receita.';
			$this->redirect($this->buildNovoUrl($returnTo, $clienteId, $produtoId));
		}

		try {
			$receitaId = $this->receitaDAO->criarComItens($data, [
				[
					'produto_id' => $produtoId,
					'posologia' => $posologia,
				],
			]);

			$_SESSION['flash_success'] = 'Receita #' . $receitaId . ' cadastrada com sucesso.';
			if ($returnTo !== null) {
				$this->redirect($returnTo);
			}

			$this->redirect('/receitas');
			return;
		} catch (Throwable $e) {
			$_SESSION['flash_error'] = 'Falha ao salvar receita.';
			$this->redirect($this->buildNovoUrl($returnTo, $clienteId, $produtoId));
		}
	}

	private function buildNovoUrl(?string $returnTo, string $clienteId, string $produtoId): string
	{
		$params = [];
		if ($returnTo !== null) {
			$params['return_to'] = $returnTo;
		}
		if ($clienteId !== '') {
			$params['cliente_id'] = $clienteId;
		}
		if ($produtoId !== '') {
			$params['produto_id'] = $produtoId;
		}

		if (empty($params)) {
			return '/receitas/novo';
		}

		return '/receitas/novo?' . http_build_query($params);
	}

	private function sanitizeInternalPath(string $path): ?string
	{
		$path = trim($path);
		if ($path === '') {
			return null;
		}

		if (!str_starts_with($path, '/')) {
			return null;
		}

		if (str_starts_with($path, '//')) {
			return null;
		}

		if (preg_match('/^[a-z][a-z0-9+.-]*:/i', $path) === 1) {
			return null;
		}

		return $path;
	}
}

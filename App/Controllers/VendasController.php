<?php

namespace App\Controllers;

use App\DAO\ClienteDAO;
use App\DAO\FuncionarioDAO;
use App\DAO\ProdutoDAO;
use App\DAO\ReceitaDAO;
use App\DAO\VendaDAO;
use Exception;
use RuntimeException;
use Throwable;

class VendasController extends Controller
{
	private VendaDAO $vendaDAO;
	private ClienteDAO $clienteDAO;
	private FuncionarioDAO $funcionarioDAO;
	private ProdutoDAO $produtoDAO;
	private ReceitaDAO $receitaDAO;

	public function __construct(array $segments = [])
	{
		parent::__construct($segments);
		$this->vendaDAO = new VendaDAO();
		$this->clienteDAO = new ClienteDAO();
		$this->funcionarioDAO = new FuncionarioDAO();
		$this->produtoDAO = new ProdutoDAO();
		$this->receitaDAO = new ReceitaDAO();
	}

	public function executar(): void
	{
		$action = $this->segments[1] ?? 'nova';

		if ($action === 'criar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
			$this->criar();
			return;
		}

		if ($action === 'adicionar-item' && $_SERVER['REQUEST_METHOD'] === 'POST') {
			$this->adicionarItem();
			return;
		}

		if ($action === 'receitas-validas') {
			$this->receitasValidas();
			return;
		}

		$this->nova();
	}

	private function nova(): void
	{
		$vendaId = isset($this->request['venda_id']) ? (int) $this->request['venda_id'] : null;
		$venda = null;
		$itens = [];

		if ($vendaId !== null && $vendaId > 0) {
			$venda = $this->vendaDAO->buscarVenda($vendaId);
			if ($venda !== null) {
				$itens = $this->vendaDAO->listarItensDaVenda($vendaId);
			}
		}

		$this->addParam('clientes', $this->clienteDAO->listar());
		$this->addParam('funcionarios', $this->funcionarioDAO->listar());
		$this->addParam('produtos', $this->produtoDAO->listar());
		$this->addParam('venda', $venda);
		$this->addParam('itens', $itens);
		$this->render('vendas/nova');
	}

	private function criar(): void
	{
		$clienteId = ($this->request['cliente_id'] ?? '') === '' ? null : (int) $this->request['cliente_id'];
		$funcionarioId = (int) ($this->request['funcionario_id'] ?? 0);

		if ($funcionarioId <= 0) {
			$_SESSION['flash_error'] = 'Selecione um funcionario.';
			$this->redirect('/vendas/nova');
		}

		$vendaId = $this->vendaDAO->criarVenda($clienteId, $funcionarioId);
		$_SESSION['flash_success'] = 'Venda iniciada com sucesso.';
		$this->redirect('/vendas/nova?venda_id=' . $vendaId);
	}

	private function adicionarItem(): void
	{
		$vendaId = (int) ($this->request['venda_id'] ?? 0);
		$produtoId = (int) ($this->request['produto_id'] ?? 0);
		$quantidade = (int) ($this->request['quantidade'] ?? 0);
		$receitaIdRaw = trim((string) ($this->request['receita_id'] ?? ''));
		$receitaId = $receitaIdRaw === '' ? null : (int) $receitaIdRaw;

		if ($vendaId <= 0 || $produtoId <= 0 || $quantidade <= 0) {
			$_SESSION['flash_error'] = 'Informe venda, produto e quantidade validos.';
			$this->redirect('/vendas/nova?venda_id=' . $vendaId);
		}

		try {
			$valor = $this->vendaDAO->adicionarItemFefo($vendaId, $produtoId, $quantidade, $receitaId);
			$_SESSION['flash_success'] = 'Item adicionado. Total deste lancamento: R$ ' . number_format($valor, 2, ',', '.');
		} catch (Exception $e) {
			$_SESSION['flash_error'] = $e->getMessage();
		}

		$this->redirect('/vendas/nova?venda_id=' . $vendaId);
	}

	private function receitasValidas(): void
	{
		$vendaId = (int) ($this->request['venda_id'] ?? 0);
		$produtoId = (int) ($this->request['produto_id'] ?? 0);

		if ($vendaId <= 0 || $produtoId <= 0) {
			$this->respondJson([
				'ok' => false,
				'items' => [],
				'message' => 'Parametros invalidos.',
			], 422);
			return;
		}

		try {
			$venda = $this->vendaDAO->buscarVenda($vendaId);
			if ($venda === null) {
				throw new RuntimeException('Venda nao encontrada.');
			}

			$clienteId = isset($venda['cliente_id']) ? (int) $venda['cliente_id'] : 0;
			if ($clienteId <= 0) {
				$this->respondJson([
					'ok' => true,
					'items' => [],
					'message' => 'Venda sem cliente vinculado.',
				], 200);
				return;
			}

			$receitas = $this->receitaDAO->listarValidasParaClienteProduto($clienteId, $produtoId);

			$items = [];
			foreach ($receitas as $receita) {
				$items[] = [
					'id' => (int) $receita['id'],
					'data_receita' => (string) $receita['data_receita'],
					'medico_nome' => (string) $receita['medico_nome'],
					'crm' => (string) $receita['crm'],
				];
			}

			$this->respondJson([
				'ok' => true,
				'items' => $items,
				'message' => empty($items) ? 'Nenhuma receita valida encontrada.' : 'Receitas carregadas.',
			], 200);
			return;
		} catch (Throwable $e) {
			$this->respondJson([
				'ok' => false,
				'items' => [],
				'message' => 'Falha ao consultar receitas.',
			], 500);
			return;
		}
	}

	private function respondJson(array $payload, int $statusCode): void
	{
		http_response_code($statusCode);
		header('Content-Type: application/json; charset=UTF-8');
		echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		exit;
	}
}

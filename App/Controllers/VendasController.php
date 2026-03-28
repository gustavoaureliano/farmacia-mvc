<?php

namespace App\Controllers;

use App\DAO\ClienteDAO;
use App\DAO\FuncionarioDAO;
use App\DAO\ProdutoDAO;
use App\DAO\VendaDAO;
use Exception;

class VendasController extends Controller
{
	private VendaDAO $vendaDAO;
	private ClienteDAO $clienteDAO;
	private FuncionarioDAO $funcionarioDAO;
	private ProdutoDAO $produtoDAO;

	public function __construct(array $segments = [])
	{
		parent::__construct($segments);
		$this->vendaDAO = new VendaDAO();
		$this->clienteDAO = new ClienteDAO();
		$this->funcionarioDAO = new FuncionarioDAO();
		$this->produtoDAO = new ProdutoDAO();
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
}

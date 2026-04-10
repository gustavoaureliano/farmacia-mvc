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
		$action = $this->segments[1] ?? 'listar';

		if ($action === 'nova') {
			$this->nova();
			return;
		}

		if ($action === 'listar') {
			$this->listar();
			return;
		}

		if ($action === 'criar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
			$this->criar();
			return;
		}

		if ($action === 'adicionar-item' && $_SERVER['REQUEST_METHOD'] === 'POST') {
			$this->adicionarItem();
			return;
		}

		if ($action === 'atualizar-item' && $_SERVER['REQUEST_METHOD'] === 'POST') {
			$this->atualizarItem();
			return;
		}

		if ($action === 'remover-item' && $_SERVER['REQUEST_METHOD'] === 'POST') {
			$this->removerItem();
			return;
		}

		if ($action === 'finalizar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
			$this->finalizar();
			return;
		}

		if ($action === 'cancelar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
			$this->cancelar();
			return;
		}

		if ($action === 'receitas-validas') {
			$this->receitasValidas();
			return;
		}

		if ($action === 'exportar-csv') {
			$this->exportarCsv();
			return;
		}

		$this->listar();
	}

	private function listar(): void
	{
		$filtros = $this->extractFiltros();
		$ordem = $this->extractOrdem();

		$vendas = $this->vendaDAO->listarVendasComResumo($filtros, $ordem);
		$vendaIds = array_map(static fn ($venda) => (int) $venda['id'], $vendas);
		$itens = $this->vendaDAO->listarItensPorVendas($vendaIds);

		$itensPorVenda = [];
		foreach ($itens as $item) {
			$vendaId = (int) $item['venda_id'];
			if (!isset($itensPorVenda[$vendaId])) {
				$itensPorVenda[$vendaId] = [];
			}

			$itensPorVenda[$vendaId][] = $item;
		}

		$this->addParam('vendas', $vendas);
		$this->addParam('itensPorVenda', $itensPorVenda);
		$this->addParam('clientes', $this->clienteDAO->listar());
		$this->addParam('funcionarios', $this->funcionarioDAO->listar());
		$this->addParam('filtros', $filtros);
		$this->addParam('ordem', $ordem);
		$this->render('vendas/listar');
	}

	private function nova(): void
	{
		$vendaId = isset($this->request['venda_id']) ? (int) $this->request['venda_id'] : null;
		$finalizadaId = isset($this->request['finalizada_id']) ? (int) $this->request['finalizada_id'] : null;
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
		$this->addParam('finalizadaId', $finalizadaId !== null && $finalizadaId > 0 ? $finalizadaId : null);
		$this->render('vendas/nova');
	}

	private function criar(): void
	{
		$clienteIdRaw = trim((string) ($this->request['cliente_id'] ?? ''));
		$clienteId = $clienteIdRaw === '' ? null : $clienteIdRaw;
		$funcionarioId = trim((string) ($this->request['funcionario_id'] ?? ''));

		if ($funcionarioId === '') {
			$_SESSION['flash_error'] = 'Selecione um funcionario.';
			$this->redirect($this->url('/vendas/nova'));
		}

		$vendaId = $this->vendaDAO->criarVenda($clienteId, $funcionarioId);
		$_SESSION['flash_success'] = 'Venda iniciada com sucesso.';
		$this->redirect($this->url('/vendas/nova?venda_id=' . $vendaId));
	}

	private function adicionarItem(): void
	{
		$vendaId = (int) ($this->request['venda_id'] ?? 0);
		$produtoId = trim((string) ($this->request['produto_id'] ?? ''));
		$quantidade = (int) ($this->request['quantidade'] ?? 0);
		$receitaIdRaw = trim((string) ($this->request['receita_id'] ?? ''));
		$receitaId = $receitaIdRaw === '' ? null : (int) $receitaIdRaw;

		if ($vendaId <= 0 || $produtoId === '' || $quantidade <= 0) {
			$_SESSION['flash_error'] = 'Informe venda, produto e quantidade validos.';
			$this->redirect($this->url('/vendas/nova?venda_id=' . $vendaId));
		}

		try {
			$valor = $this->vendaDAO->adicionarItemFefo($vendaId, $produtoId, $quantidade, $receitaId);
			$_SESSION['flash_success'] = 'Item adicionado. Total deste lancamento: R$ ' . number_format($valor, 2, ',', '.');
		} catch (Exception $e) {
			$_SESSION['flash_error'] = $e->getMessage();
		}

		$this->redirect($this->url('/vendas/nova?venda_id=' . $vendaId));
	}

	private function atualizarItem(): void
	{
		$vendaId = (int) ($this->request['venda_id'] ?? 0);
		$codBarras = trim((string) ($this->request['item_cod_barras'] ?? ''));
		$lote = trim((string) ($this->request['item_lote'] ?? ''));
		$quantidade = (int) ($this->request['quantidade'] ?? 0);

		if ($vendaId <= 0 || $codBarras === '' || $lote === '' || $quantidade <= 0) {
			$_SESSION['flash_error'] = 'Informe venda, item e quantidade validos.';
			$this->redirect($this->url('/vendas/nova?venda_id=' . $vendaId));
		}

		try {
			$this->vendaDAO->atualizarQuantidadeItem($vendaId, $codBarras, $lote, $quantidade);
			$_SESSION['flash_success'] = 'Quantidade do item atualizada com sucesso.';
		} catch (Exception $e) {
			$_SESSION['flash_error'] = $e->getMessage();
		}

		$this->redirect($this->url('/vendas/nova?venda_id=' . $vendaId));
	}

	private function removerItem(): void
	{
		$vendaId = (int) ($this->request['venda_id'] ?? 0);
		$itemKey = trim((string) ($this->request['item_key'] ?? ''));
		$codBarras = trim((string) ($this->request['item_cod_barras'] ?? ''));
		$lote = trim((string) ($this->request['item_lote'] ?? ''));

		if ($itemKey !== '' && str_contains($itemKey, '|')) {
			[$codBarrasParsed, $loteParsed] = explode('|', $itemKey, 2);
			$codBarras = trim($codBarrasParsed);
			$lote = trim($loteParsed);
		}

		if ($vendaId <= 0 || $codBarras === '' || $lote === '') {
			$_SESSION['flash_error'] = 'Informe venda e item validos.';
			$this->redirect($this->url('/vendas/nova?venda_id=' . $vendaId));
		}

		try {
			$this->vendaDAO->removerItem($vendaId, $codBarras, $lote);
			$_SESSION['flash_success'] = 'Item removido da venda.';
		} catch (Exception $e) {
			$_SESSION['flash_error'] = $e->getMessage();
		}

		$this->redirect($this->url('/vendas/nova?venda_id=' . $vendaId));
	}

	private function finalizar(): void
	{
		$vendaId = (int) ($this->request['venda_id'] ?? 0);
		if ($vendaId <= 0) {
			$_SESSION['flash_error'] = 'Venda invalida para finalizar.';
			$this->redirect($this->url('/vendas/nova'));
		}

		$quantidadesRaw = $this->request['quantidades'] ?? [];
		$quantidades = is_array($quantidadesRaw) ? $quantidadesRaw : [];

		try {
			$this->vendaDAO->sincronizarQuantidadesParaFinalizacao($vendaId, $quantidades);
			$this->vendaDAO->finalizarVenda($vendaId);
			$_SESSION['flash_success'] = 'Venda #' . $vendaId . ' finalizada com sucesso.';
			$this->redirect($this->url('/vendas/nova?finalizada_id=' . $vendaId));
			return;
		} catch (Exception $e) {
			$_SESSION['flash_error'] = $e->getMessage();
			$this->redirect($this->url('/vendas/nova?venda_id=' . $vendaId));
		}
	}

	private function cancelar(): void
	{
		$vendaId = (int) ($this->request['venda_id'] ?? 0);
		$returnTo = $this->sanitizeInternalPath((string) ($this->request['return_to'] ?? ''));

		if ($vendaId <= 0) {
			$_SESSION['flash_error'] = 'Venda invalida para cancelamento.';
			$this->redirect($this->url('/vendas/listar'));
		}

		try {
			$this->vendaDAO->cancelarVenda($vendaId);
			$_SESSION['flash_success'] = 'Venda #' . $vendaId . ' cancelada com sucesso. Estoque e receitas foram liberados.';
		} catch (Exception $e) {
			$_SESSION['flash_error'] = $e->getMessage();
		}

		if ($returnTo !== null) {
			$this->redirect($this->url($returnTo));
			return;
		}

		$this->redirect($this->url('/vendas/listar?venda_id=' . $vendaId));
	}

	private function receitasValidas(): void
	{
		$vendaId = (int) ($this->request['venda_id'] ?? 0);
		$produtoId = trim((string) ($this->request['produto_id'] ?? ''));

		if ($vendaId <= 0 || $produtoId === '') {
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

			$clienteId = isset($venda['cliente_id']) && $venda['cliente_id'] !== null ? (string) $venda['cliente_id'] : '';
			if ($clienteId === '') {
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

	private function exportarCsv(): void
	{
		$filtros = $this->extractFiltros();
		$ordem = $this->extractOrdem();
		$modo = trim((string) ($this->request['modo'] ?? 'detalhado'));
		if (!in_array($modo, ['detalhado', 'resumo'], true)) {
			$modo = 'detalhado';
		}

		$linhas = $modo === 'resumo'
			? $this->vendaDAO->listarParaExportacaoResumo($filtros, $ordem)
			: $this->vendaDAO->listarParaExportacaoDetalhada($filtros, $ordem);

		$agora = date('Y-m-d_His');
		$nomeArquivo = 'vendas_' . $modo . '_' . $agora . '.csv';

		header('Content-Type: text/csv; charset=UTF-8');
		header('Content-Disposition: attachment; filename="' . $nomeArquivo . '"');

		$output = fopen('php://output', 'w');
		if ($output === false) {
			http_response_code(500);
			echo 'Falha ao gerar arquivo CSV.';
			exit;
		}

		fwrite($output, "\xEF\xBB\xBF");

		if ($modo === 'resumo') {
			fputcsv($output, ['venda_id', 'data_venda', 'cliente', 'funcionario', 'total_itens', 'valor_total'], ';');
			foreach ($linhas as $linha) {
				fputcsv($output, [
					(string) $linha['venda_id'],
					(string) $linha['data_venda'],
					(string) $linha['cliente_nome'],
					(string) $linha['funcionario_nome'],
					(string) $linha['total_itens'],
					number_format((float) $linha['valor_total'], 2, '.', ''),
				], ';');
			}
		} else {
			fputcsv($output, ['venda_id', 'data_venda', 'cliente', 'funcionario', 'venda_total', 'item_id', 'produto', 'lote', 'validade', 'quantidade', 'preco_unitario', 'subtotal', 'receita_id'], ';');
			foreach ($linhas as $linha) {
				fputcsv($output, [
					(string) $linha['venda_id'],
					(string) $linha['data_venda'],
					(string) $linha['cliente_nome'],
					(string) $linha['funcionario_nome'],
					number_format((float) $linha['valor_total'], 2, '.', ''),
					$linha['item_id'] !== null ? (string) $linha['item_id'] : '',
					$linha['produto_nome'] !== null ? (string) $linha['produto_nome'] : '',
					$linha['numero_lote'] !== null ? (string) $linha['numero_lote'] : '',
					$linha['validade'] !== null ? (string) $linha['validade'] : '',
					$linha['quantidade'] !== null ? (string) $linha['quantidade'] : '',
					$linha['preco_unitario_momento'] !== null ? number_format((float) $linha['preco_unitario_momento'], 2, '.', '') : '',
					$linha['subtotal'] !== null ? number_format((float) $linha['subtotal'], 2, '.', '') : '',
					$linha['receita_id'] !== null ? (string) $linha['receita_id'] : '',
				], ';');
			}
		}

		fclose($output);
		exit;
	}

	private function extractFiltros(): array
	{
		return [
			'venda_id' => (int) ($this->request['venda_id'] ?? 0),
			'data_inicio' => trim((string) ($this->request['data_inicio'] ?? '')),
			'data_fim' => trim((string) ($this->request['data_fim'] ?? '')),
			'cliente_id' => trim((string) ($this->request['cliente_id'] ?? '')),
			'funcionario_id' => trim((string) ($this->request['funcionario_id'] ?? '')),
		];
	}

	private function extractOrdem(): string
	{
		$ordem = trim((string) ($this->request['ordem'] ?? 'data_desc'));
		if (!in_array($ordem, ['data_desc', 'data_asc', 'total_desc', 'total_asc'], true)) {
			return 'data_desc';
		}

		return $ordem;
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

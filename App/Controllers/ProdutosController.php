<?php

namespace App\Controllers;

use App\DAO\Connection;
use App\DAO\LoteDAO;
use App\DAO\ProdutoDAO;
use RuntimeException;
use Throwable;

class ProdutosController extends Controller
{
	private ProdutoDAO $produtoDAO;
	private LoteDAO $loteDAO;

	public function __construct(array $segments = [])
	{
		parent::__construct($segments);
		$this->produtoDAO = new ProdutoDAO();
		$this->loteDAO = new LoteDAO();
	}

	public function executar(): void
	{
		$action = $this->segments[1] ?? 'listar';

		if ($action === 'buscar') {
			$this->buscar();
			return;
		}

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
		$this->addParam('produtos', $this->produtoDAO->listar());
		$this->render('produtos/listar');
	}

	private function novo(): void
	{
		$returnTo = $this->sanitizeInternalPath((string) ($this->request['return_to'] ?? ''));
		$this->addParam('returnTo', $returnTo);
		$this->render('produtos/novo');
	}

	private function salvar(): void
	{
		$returnTo = $this->sanitizeInternalPath((string) ($this->request['return_to'] ?? ''));
		$tipo = $this->request['tipo'] ?? 'generico';
		if (!in_array($tipo, ['generico', 'similar', 'referencia'], true)) {
			$tipo = 'generico';
		}

		$data = [
			'nome' => trim((string) ($this->request['nome'] ?? '')),
			'principio_ativo' => trim((string) ($this->request['principio_ativo'] ?? '')),
			'marca_laboratorio' => trim((string) ($this->request['marca_laboratorio'] ?? '')),
			'tipo' => $tipo,
			'exige_receita' => isset($this->request['exige_receita']) ? 1 : 0,
			'preco_atual' => (float) ($this->request['preco_atual'] ?? 0),
			'codigo_barras' => trim((string) ($this->request['codigo_barras'] ?? '')),
		];

		if ($data['nome'] === '' || $data['preco_atual'] <= 0 || $data['codigo_barras'] === '') {
			$_SESSION['flash_error'] = 'Preencha nome, preco e codigo de barras.';
			$this->redirect($this->buildNovoUrl($returnTo));
		}

		$loteInicial = [
			'numero_lote' => trim((string) ($this->request['lote_numero'] ?? '')),
			'validade' => trim((string) ($this->request['lote_validade'] ?? '')),
			'quantidade_disponivel' => (int) ($this->request['lote_quantidade'] ?? 0),
			'localizacao' => trim((string) ($this->request['lote_localizacao'] ?? '')),
		];

		$querCriarLote = $loteInicial['numero_lote'] !== '' || $loteInicial['validade'] !== '' || $loteInicial['quantidade_disponivel'] > 0 || $loteInicial['localizacao'] !== '';

		if ($querCriarLote && ($loteInicial['numero_lote'] === '' || $loteInicial['validade'] === '' || $loteInicial['quantidade_disponivel'] <= 0)) {
			$_SESSION['flash_error'] = 'Para cadastrar lote inicial, informe numero do lote, validade e quantidade maior que zero.';
			$this->redirect($this->buildNovoUrl($returnTo));
		}

		$conn = Connection::getConn();

		try {
			$conn->beginTransaction();
			$produtoId = $this->produtoDAO->criar($data);

			if ($querCriarLote) {
				$loteInicial['produto_id'] = $produtoId;
				$this->loteDAO->criar($loteInicial);
			}

			$conn->commit();
		} catch (Throwable $e) {
			if ($conn->inTransaction()) {
				$conn->rollBack();
			}

			$_SESSION['flash_error'] = 'Falha ao cadastrar produto. Verifique os dados e tente novamente.';
			$this->redirect($this->buildNovoUrl($returnTo));
			return;
		}

		$_SESSION['flash_success'] = $querCriarLote
			? 'Produto e lote inicial cadastrados com sucesso.'
			: 'Produto cadastrado com sucesso.';

		if ($returnTo !== null) {
			$this->redirect($this->appendProdutoIdToReturn($returnTo, $produtoId));
			return;
		}

		$this->redirect('/produtos');
	}

	private function buildNovoUrl(?string $returnTo): string
	{
		if ($returnTo === null) {
			return '/produtos/novo';
		}

		return '/produtos/novo?return_to=' . urlencode($returnTo);
	}

	private function appendProdutoIdToReturn(string $returnTo, int $produtoId): string
	{
		$parts = parse_url($returnTo);
		$path = $parts['path'] ?? '/';

		$params = [];
		if (isset($parts['query'])) {
			parse_str($parts['query'], $params);
		}

		if ($path === '/lotes/novo') {
			$params['produto_id'] = (string) $produtoId;
		}

		$query = http_build_query($params);
		if ($query === '') {
			return $path;
		}

		return $path . '?' . $query;
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

	private function buscar(): void
	{
		$q = trim((string) ($this->request['q'] ?? ''));
		$tipo = trim((string) ($this->request['tipo'] ?? ''));
		$exigeReceita = ($this->request['exige_receita'] ?? '') === '' ? '' : (string) $this->request['exige_receita'];
		$ativo = ($this->request['ativo'] ?? '') === '' ? 1 : (int) $this->request['ativo'];
		$limit = (int) ($this->request['limit'] ?? 20);

		$filtros = [
			'tipo' => $tipo,
			'exige_receita' => $exigeReceita,
			'ativo' => $ativo,
		];

		try {
			$items = $this->produtoDAO->buscar($q, $filtros, $limit);
			$response = [
				'ok' => true,
				'query' => $q,
				'total' => count($items),
				'items' => $this->formatarBuscaItems($items),
			];
			$this->validarContratoBusca($response);
			$this->respondJson($response, 200);
			return;
		} catch (Throwable $e) {
			$this->respondJson([
				'ok' => false,
				'query' => $q,
				'total' => 0,
				'items' => [],
				'error' => 'Falha ao buscar produtos.',
			], 500);
			return;
		}
	}

	private function formatarBuscaItems(array $items): array
	{
		$formatados = [];

		foreach ($items as $item) {
			$formatados[] = [
				'id' => (int) $item['id'],
				'nome' => (string) $item['nome'],
				'principio_ativo' => (string) ($item['principio_ativo'] ?? ''),
				'marca_laboratorio' => (string) ($item['marca_laboratorio'] ?? ''),
				'tipo' => (string) $item['tipo'],
				'exige_receita' => (int) $item['exige_receita'],
				'preco_atual' => (float) $item['preco_atual'],
				'codigo_barras' => (string) $item['codigo_barras'],
				'estoque_disponivel' => (int) ($item['estoque_disponivel'] ?? 0),
			];
		}

		return $formatados;
	}

	private function validarContratoBusca(array $response): void
	{
		if (!isset($response['ok'], $response['query'], $response['total'], $response['items'])) {
			throw new RuntimeException('Contrato JSON invalido para busca de produtos.');
		}

		if (!is_bool($response['ok']) || !is_string($response['query']) || !is_int($response['total']) || !is_array($response['items'])) {
			throw new RuntimeException('Contrato JSON invalido para busca de produtos.');
		}

		foreach ($response['items'] as $item) {
			$keys = ['id', 'nome', 'principio_ativo', 'marca_laboratorio', 'tipo', 'exige_receita', 'preco_atual', 'codigo_barras', 'estoque_disponivel'];
			foreach ($keys as $key) {
				if (!array_key_exists($key, $item)) {
					throw new RuntimeException('Contrato JSON invalido para busca de produtos.');
				}
			}
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

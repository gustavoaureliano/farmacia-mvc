<?php

namespace App\Controllers;

use App\DAO\LoteDAO;
use App\DAO\ProdutoDAO;
use Throwable;

class LotesController extends Controller
{
	private LoteDAO $loteDAO;
	private ProdutoDAO $produtoDAO;
	private const DIAS_RISCO_CRITICO = 30;
	private const DIAS_RISCO_ATENCAO = 90;

	public function __construct(array $segments = [])
	{
		parent::__construct($segments);
		$this->loteDAO = new LoteDAO();
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
		$this->addParam('lotes', $this->loteDAO->listar());
		$this->addParam('resumoEstoque', $this->mapearRiscoResumo($this->loteDAO->resumoEstoquePorProduto()));
		$this->addParam('diasRiscoAtencao', self::DIAS_RISCO_ATENCAO);
		$this->render('lotes/listar');
	}

	private function novo(): void
	{
		$returnTo = $this->sanitizeInternalPath((string) ($this->request['return_to'] ?? ''));
		$produtoCodSelecionado = trim((string) ($this->request['cod_barras'] ?? ''));

		$this->addParam('produtos', $this->produtoDAO->listar());
		$this->addParam('returnTo', $returnTo);
		$this->addParam('produtoCodSelecionado', $produtoCodSelecionado !== '' ? $produtoCodSelecionado : null);
		$this->render('lotes/novo');
	}

	private function salvar(): void
	{
		$returnTo = $this->sanitizeInternalPath((string) ($this->request['return_to'] ?? ''));
		$redirectNovo = '/estoque/novo';
		if ($returnTo !== null) {
			$redirectNovo .= '?return_to=' . urlencode($returnTo);
		}

		$data = [
			'cod_barras' => trim((string) ($this->request['cod_barras'] ?? '')),
			'numero_lote' => trim((string) ($this->request['numero_lote'] ?? '')),
			'validade' => trim((string) ($this->request['validade'] ?? '')),
			'quantidade_disponivel' => (int) ($this->request['quantidade_disponivel'] ?? 0),
			'localizacao' => trim((string) ($this->request['localizacao'] ?? '')),
		];

		if ($data['cod_barras'] === '' || $data['numero_lote'] === '' || $data['validade'] === '' || $data['quantidade_disponivel'] <= 0) {
			$_SESSION['flash_error'] = 'Informe produto, lote, validade e quantidade valida.';
			$this->redirect($redirectNovo);
		}

		try {
			$this->loteDAO->criar($data);
			$_SESSION['flash_success'] = 'Entrada de estoque cadastrada com sucesso.';
			if ($returnTo !== null) {
				$this->redirect($returnTo);
				return;
			}
			$this->redirect('/estoque');
			return;
		} catch (Throwable $e) {
			$_SESSION['flash_error'] = 'Falha ao cadastrar entrada de estoque. Verifique se o numero do lote ja existe para o produto.';
			$this->redirect($redirectNovo);
		}
	}

	private function mapearRiscoResumo(array $resumo): array
	{
		$hoje = new \DateTimeImmutable('today');
		$saida = [];

		foreach ($resumo as $row) {
			$estoque = (int) ($row['estoque_valido'] ?? 0);
			$proximaValidade = $row['proxima_validade'] ?? null;
			$risco = 'ok';
			$dias = null;

			if ($estoque <= 0 || $proximaValidade === null) {
				$risco = 'sem_estoque';
			} else {
				$validade = new \DateTimeImmutable((string) $proximaValidade);
				$intervalo = $hoje->diff($validade);
				$dias = (int) $intervalo->format('%r%a');

				if ($dias <= self::DIAS_RISCO_CRITICO) {
					$risco = 'critico';
				} elseif ($dias <= self::DIAS_RISCO_ATENCAO) {
					$risco = 'atencao';
				}
			}

			$row['dias_para_vencer'] = $dias;
			$row['risco_validade'] = $risco;
			$saida[] = $row;
		}

		return $saida;
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

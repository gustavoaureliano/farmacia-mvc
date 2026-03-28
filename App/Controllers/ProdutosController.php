<?php

namespace App\Controllers;

use App\DAO\ProdutoDAO;

class ProdutosController extends Controller
{
	private ProdutoDAO $produtoDAO;

	public function __construct(array $segments = [])
	{
		parent::__construct($segments);
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
		$this->addParam('produtos', $this->produtoDAO->listar());
		$this->render('produtos/listar');
	}

	private function novo(): void
	{
		$this->render('produtos/novo');
	}

	private function salvar(): void
	{
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
			$this->redirect('/produtos/novo');
		}

		$this->produtoDAO->criar($data);
		$_SESSION['flash_success'] = 'Produto cadastrado com sucesso.';

		$this->redirect('/produtos');
	}
}

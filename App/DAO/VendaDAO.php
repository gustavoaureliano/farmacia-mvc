<?php

namespace App\DAO;

use Exception;
use PDO;

class VendaDAO
{
	private PDO $conn;

	public function __construct()
	{
		$this->conn = Connection::getConn();
	}

	public function criarVenda(?int $clienteId, int $funcionarioId): int
	{
		$sql = 'INSERT INTO vendas (cliente_id, funcionario_id, valor_total) VALUES (:cliente_id, :funcionario_id, 0)';
		$stmt = $this->conn->prepare($sql);
		$stmt->bindValue(':cliente_id', $clienteId, $clienteId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
		$stmt->bindValue(':funcionario_id', $funcionarioId, PDO::PARAM_INT);
		$stmt->execute();

		return (int) $this->conn->lastInsertId();
	}

	public function buscarVenda(int $vendaId): ?array
	{
		$sql = 'SELECT * FROM vendas WHERE id = :id';
		$stmt = $this->conn->prepare($sql);
		$stmt->bindValue(':id', $vendaId, PDO::PARAM_INT);
		$stmt->execute();

		$venda = $stmt->fetch();

		return $venda ?: null;
	}

	public function listarItensDaVenda(int $vendaId): array
	{
		$sql = 'SELECT iv.*, p.nome AS produto_nome, le.numero_lote, le.validade
				FROM itens_venda iv
				INNER JOIN produtos p ON p.id = iv.produto_id
				INNER JOIN lotes_estoque le ON le.id = iv.lote_id
				WHERE iv.venda_id = :venda_id
				ORDER BY iv.id ASC';
		$stmt = $this->conn->prepare($sql);
		$stmt->bindValue(':venda_id', $vendaId, PDO::PARAM_INT);
		$stmt->execute();

		return $stmt->fetchAll();
	}

	public function adicionarItemFefo(int $vendaId, int $produtoId, int $quantidade, ?int $receitaId = null): float
	{
		if ($quantidade <= 0) {
			throw new Exception('Quantidade deve ser maior que zero.');
		}

		$this->conn->beginTransaction();

		try {
			$venda = $this->buscarVenda($vendaId);
			if ($venda === null) {
				throw new Exception('Venda nao encontrada.');
			}

			$produtoStmt = $this->conn->prepare('SELECT * FROM produtos WHERE id = :id AND ativo = 1');
			$produtoStmt->bindValue(':id', $produtoId, PDO::PARAM_INT);
			$produtoStmt->execute();
			$produto = $produtoStmt->fetch();

			if ($produto === false) {
				throw new Exception('Produto nao encontrado.');
			}

			if ((int) $produto['exige_receita'] === 1) {
				if ($receitaId === null) {
					throw new Exception('Produto exige receita.');
				}

				if ($venda['cliente_id'] === null) {
					throw new Exception('Venda de controlado exige cliente vinculado.');
				}

				$receitaSql = 'SELECT 1
							   FROM receitas r
							   INNER JOIN receita_itens ri ON ri.receita_id = r.id
							   WHERE r.id = :receita_id
								 AND r.cliente_id = :cliente_id
								 AND ri.produto_id = :produto_id
							   LIMIT 1';
				$receitaStmt = $this->conn->prepare($receitaSql);
				$receitaStmt->bindValue(':receita_id', $receitaId, PDO::PARAM_INT);
				$receitaStmt->bindValue(':cliente_id', (int) $venda['cliente_id'], PDO::PARAM_INT);
				$receitaStmt->bindValue(':produto_id', $produtoId, PDO::PARAM_INT);
				$receitaStmt->execute();

				if ($receitaStmt->fetchColumn() === false) {
					throw new Exception('Receita invalida para o cliente/produto.');
				}
			}

			$lotesSql = 'SELECT id, quantidade_disponivel
						 FROM lotes_estoque
						 WHERE produto_id = :produto_id
						   AND quantidade_disponivel > 0
						   AND validade >= CURDATE()
						 ORDER BY validade ASC, id ASC
						 FOR UPDATE';
			$lotesStmt = $this->conn->prepare($lotesSql);
			$lotesStmt->bindValue(':produto_id', $produtoId, PDO::PARAM_INT);
			$lotesStmt->execute();
			$lotes = $lotesStmt->fetchAll();

			$restante = $quantidade;
			$precoUnitario = (float) $produto['preco_atual'];
			$totalAdicionado = 0.0;

			foreach ($lotes as $lote) {
				if ($restante <= 0) {
					break;
				}

				$disponivel = (int) $lote['quantidade_disponivel'];
				$consumir = min($restante, $disponivel);

				if ($consumir <= 0) {
					continue;
				}

				$novoSaldo = $disponivel - $consumir;
				$subtotal = $consumir * $precoUnitario;

				$updateLote = $this->conn->prepare('UPDATE lotes_estoque SET quantidade_disponivel = :saldo WHERE id = :id');
				$updateLote->bindValue(':saldo', $novoSaldo, PDO::PARAM_INT);
				$updateLote->bindValue(':id', (int) $lote['id'], PDO::PARAM_INT);
				$updateLote->execute();

				$insertItem = $this->conn->prepare('INSERT INTO itens_venda
					(venda_id, produto_id, lote_id, quantidade, preco_unitario_momento, subtotal, receita_id)
					VALUES
					(:venda_id, :produto_id, :lote_id, :quantidade, :preco, :subtotal, :receita_id)');
				$insertItem->bindValue(':venda_id', $vendaId, PDO::PARAM_INT);
				$insertItem->bindValue(':produto_id', $produtoId, PDO::PARAM_INT);
				$insertItem->bindValue(':lote_id', (int) $lote['id'], PDO::PARAM_INT);
				$insertItem->bindValue(':quantidade', $consumir, PDO::PARAM_INT);
				$insertItem->bindValue(':preco', $precoUnitario);
				$insertItem->bindValue(':subtotal', $subtotal);
				$insertItem->bindValue(':receita_id', $receitaId, $receitaId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
				$insertItem->execute();

				$restante -= $consumir;
				$totalAdicionado += $subtotal;
			}

			if ($restante > 0) {
				throw new Exception('Estoque insuficiente para atender a quantidade solicitada.');
			}

			$updateVenda = $this->conn->prepare('UPDATE vendas SET valor_total = valor_total + :valor WHERE id = :id');
			$updateVenda->bindValue(':valor', $totalAdicionado);
			$updateVenda->bindValue(':id', $vendaId, PDO::PARAM_INT);
			$updateVenda->execute();

			$this->conn->commit();

			return $totalAdicionado;
		} catch (Exception $e) {
			$this->conn->rollBack();
			throw $e;
		}
	}

	public function atualizarQuantidadeItem(int $itemId, int $novaQuantidade): void
	{
		if ($novaQuantidade <= 0) {
			throw new Exception('Quantidade deve ser maior que zero.');
		}

		$this->conn->beginTransaction();

		try {
			$item = $this->buscarItemVenda($itemId);
			if ($item === null) {
				throw new Exception('Item da venda nao encontrado.');
			}

			$quantidadeAtual = (int) $item['quantidade'];
			$delta = $novaQuantidade - $quantidadeAtual;
			if ($delta === 0) {
				$this->conn->commit();
				return;
			}

			$loteStmt = $this->conn->prepare('SELECT id, quantidade_disponivel FROM lotes_estoque WHERE id = :id FOR UPDATE');
			$loteStmt->bindValue(':id', (int) $item['lote_id'], PDO::PARAM_INT);
			$loteStmt->execute();
			$lote = $loteStmt->fetch();
			if ($lote === false) {
				throw new Exception('Lote do item nao encontrado.');
			}

			$saldoLote = (int) $lote['quantidade_disponivel'];
			$novoSaldoLote = $saldoLote - $delta;
			if ($novoSaldoLote < 0) {
				throw new Exception('Estoque insuficiente no lote para aumentar a quantidade.');
			}

			$preco = (float) $item['preco_unitario_momento'];
			$novoSubtotal = $novaQuantidade * $preco;

			$updateItem = $this->conn->prepare('UPDATE itens_venda SET quantidade = :quantidade, subtotal = :subtotal WHERE id = :id');
			$updateItem->bindValue(':quantidade', $novaQuantidade, PDO::PARAM_INT);
			$updateItem->bindValue(':subtotal', $novoSubtotal);
			$updateItem->bindValue(':id', $itemId, PDO::PARAM_INT);
			$updateItem->execute();

			$updateLote = $this->conn->prepare('UPDATE lotes_estoque SET quantidade_disponivel = :saldo WHERE id = :id');
			$updateLote->bindValue(':saldo', $novoSaldoLote, PDO::PARAM_INT);
			$updateLote->bindValue(':id', (int) $item['lote_id'], PDO::PARAM_INT);
			$updateLote->execute();

			$this->recalcularTotalVenda((int) $item['venda_id']);
			$this->conn->commit();
		} catch (Exception $e) {
			$this->conn->rollBack();
			throw $e;
		}
	}

	public function removerItem(int $itemId): void
	{
		$this->conn->beginTransaction();

		try {
			$item = $this->buscarItemVenda($itemId);
			if ($item === null) {
				throw new Exception('Item da venda nao encontrado.');
			}

			$loteStmt = $this->conn->prepare('SELECT id, quantidade_disponivel FROM lotes_estoque WHERE id = :id FOR UPDATE');
			$loteStmt->bindValue(':id', (int) $item['lote_id'], PDO::PARAM_INT);
			$loteStmt->execute();
			$lote = $loteStmt->fetch();
			if ($lote === false) {
				throw new Exception('Lote do item nao encontrado.');
			}

			$novoSaldo = (int) $lote['quantidade_disponivel'] + (int) $item['quantidade'];

			$deleteItem = $this->conn->prepare('DELETE FROM itens_venda WHERE id = :id');
			$deleteItem->bindValue(':id', $itemId, PDO::PARAM_INT);
			$deleteItem->execute();

			$updateLote = $this->conn->prepare('UPDATE lotes_estoque SET quantidade_disponivel = :saldo WHERE id = :id');
			$updateLote->bindValue(':saldo', $novoSaldo, PDO::PARAM_INT);
			$updateLote->bindValue(':id', (int) $item['lote_id'], PDO::PARAM_INT);
			$updateLote->execute();

			$this->recalcularTotalVenda((int) $item['venda_id']);
			$this->conn->commit();
		} catch (Exception $e) {
			$this->conn->rollBack();
			throw $e;
		}
	}

	public function finalizarVenda(int $vendaId): void
	{
		$venda = $this->buscarVenda($vendaId);
		if ($venda === null) {
			throw new Exception('Venda nao encontrada.');
		}

		$this->recalcularTotalVenda($vendaId);
	}

	public function sincronizarQuantidadesParaFinalizacao(int $vendaId, array $quantidades): void
	{
		$venda = $this->buscarVenda($vendaId);
		if ($venda === null) {
			throw new Exception('Venda nao encontrada.');
		}

		$this->conn->beginTransaction();

		try {
			$stmtItens = $this->conn->prepare('SELECT id, lote_id, quantidade, preco_unitario_momento
									 FROM itens_venda
									 WHERE venda_id = :venda_id
									 FOR UPDATE');
			$stmtItens->bindValue(':venda_id', $vendaId, PDO::PARAM_INT);
			$stmtItens->execute();
			$itens = $stmtItens->fetchAll();

			$itensMap = [];
			foreach ($itens as $item) {
				$itensMap[(int) $item['id']] = $item;
			}

			foreach ($quantidades as $itemIdRaw => $novaQtdRaw) {
				$itemId = (int) $itemIdRaw;
				$novaQuantidade = (int) $novaQtdRaw;

				if ($itemId <= 0 || !isset($itensMap[$itemId])) {
					throw new Exception('Item invalido para finalizar a venda.');
				}

				if ($novaQuantidade <= 0) {
					throw new Exception('Quantidade deve ser maior que zero para todos os itens.');
				}

				$itemAtual = $itensMap[$itemId];
				$quantidadeAtual = (int) $itemAtual['quantidade'];
				$delta = $novaQuantidade - $quantidadeAtual;

				if ($delta === 0) {
					continue;
				}

				$loteStmt = $this->conn->prepare('SELECT id, quantidade_disponivel FROM lotes_estoque WHERE id = :id FOR UPDATE');
				$loteStmt->bindValue(':id', (int) $itemAtual['lote_id'], PDO::PARAM_INT);
				$loteStmt->execute();
				$lote = $loteStmt->fetch();
				if ($lote === false) {
					throw new Exception('Lote do item nao encontrado.');
				}

				$saldoLote = (int) $lote['quantidade_disponivel'];
				$novoSaldoLote = $saldoLote - $delta;
				if ($novoSaldoLote < 0) {
					throw new Exception('Estoque insuficiente para atualizar quantidades antes da finalizacao.');
				}

				$preco = (float) $itemAtual['preco_unitario_momento'];
				$novoSubtotal = $novaQuantidade * $preco;

				$updateItem = $this->conn->prepare('UPDATE itens_venda SET quantidade = :quantidade, subtotal = :subtotal WHERE id = :id');
				$updateItem->bindValue(':quantidade', $novaQuantidade, PDO::PARAM_INT);
				$updateItem->bindValue(':subtotal', $novoSubtotal);
				$updateItem->bindValue(':id', $itemId, PDO::PARAM_INT);
				$updateItem->execute();

				$updateLote = $this->conn->prepare('UPDATE lotes_estoque SET quantidade_disponivel = :saldo WHERE id = :id');
				$updateLote->bindValue(':saldo', $novoSaldoLote, PDO::PARAM_INT);
				$updateLote->bindValue(':id', (int) $itemAtual['lote_id'], PDO::PARAM_INT);
				$updateLote->execute();
			}

			$this->recalcularTotalVenda($vendaId);
			$this->conn->commit();
		} catch (Exception $e) {
			$this->conn->rollBack();
			throw $e;
		}
	}

	public function listarVendasComResumo(array $filtros = [], string $ordem = 'data_desc'): array
	{
		[$whereSql, $params] = $this->buildFiltrosSql($filtros);
		$orderSql = $this->buildOrderSql($ordem);

		$sql = 'SELECT v.id,
				v.data_venda,
				v.valor_total,
				v.cliente_id,
				v.funcionario_id,
				c.nome AS cliente_nome,
				f.nome AS funcionario_nome,
				COUNT(iv.id) AS total_itens
			FROM vendas v
			LEFT JOIN clientes c ON c.id = v.cliente_id
			INNER JOIN funcionarios f ON f.id = v.funcionario_id
			LEFT JOIN itens_venda iv ON iv.venda_id = v.id
			' . $whereSql . '
			GROUP BY v.id, v.data_venda, v.valor_total, v.cliente_id, v.funcionario_id, c.nome, f.nome
			' . $orderSql;

		$stmt = $this->conn->prepare($sql);
		$this->bindFiltros($stmt, $params);
		$stmt->execute();

		return $stmt->fetchAll();
	}

	public function listarItensPorVendas(array $vendaIds): array
	{
		if (empty($vendaIds)) {
			return [];
		}

		$ids = array_values(array_unique(array_map('intval', $vendaIds)));
		$placeholders = implode(', ', array_fill(0, count($ids), '?'));

		$sql = 'SELECT iv.id,
				iv.venda_id,
				iv.quantidade,
				iv.preco_unitario_momento,
				iv.subtotal,
				iv.receita_id,
				p.nome AS produto_nome,
				le.numero_lote,
				le.validade
			FROM itens_venda iv
			INNER JOIN produtos p ON p.id = iv.produto_id
			INNER JOIN lotes_estoque le ON le.id = iv.lote_id
			WHERE iv.venda_id IN (' . $placeholders . ')
			ORDER BY iv.venda_id DESC, iv.id ASC';

		$stmt = $this->conn->prepare($sql);
		foreach ($ids as $index => $id) {
			$stmt->bindValue($index + 1, $id, PDO::PARAM_INT);
		}
		$stmt->execute();

		return $stmt->fetchAll();
	}

	public function listarParaExportacaoDetalhada(array $filtros = [], string $ordem = 'data_desc'): array
	{
		[$whereSql, $params] = $this->buildFiltrosSql($filtros);
		$orderSql = $this->buildOrderSql($ordem);

		$sql = 'SELECT v.id AS venda_id,
				v.data_venda,
				COALESCE(c.nome, "Sem cliente") AS cliente_nome,
				f.nome AS funcionario_nome,
				v.valor_total,
				iv.id AS item_id,
				p.nome AS produto_nome,
				le.numero_lote,
				le.validade,
				iv.quantidade,
				iv.preco_unitario_momento,
				iv.subtotal,
				iv.receita_id
			FROM vendas v
			LEFT JOIN clientes c ON c.id = v.cliente_id
			INNER JOIN funcionarios f ON f.id = v.funcionario_id
			LEFT JOIN itens_venda iv ON iv.venda_id = v.id
			LEFT JOIN produtos p ON p.id = iv.produto_id
			LEFT JOIN lotes_estoque le ON le.id = iv.lote_id
			' . $whereSql . '
			' . $orderSql . ', iv.id ASC';

		$stmt = $this->conn->prepare($sql);
		$this->bindFiltros($stmt, $params);
		$stmt->execute();

		return $stmt->fetchAll();
	}

	public function listarParaExportacaoResumo(array $filtros = [], string $ordem = 'data_desc'): array
	{
		[$whereSql, $params] = $this->buildFiltrosSql($filtros);
		$orderSql = $this->buildOrderSql($ordem);

		$sql = 'SELECT v.id AS venda_id,
				v.data_venda,
				COALESCE(c.nome, "Sem cliente") AS cliente_nome,
				f.nome AS funcionario_nome,
				COUNT(iv.id) AS total_itens,
				v.valor_total
			FROM vendas v
			LEFT JOIN clientes c ON c.id = v.cliente_id
			INNER JOIN funcionarios f ON f.id = v.funcionario_id
			LEFT JOIN itens_venda iv ON iv.venda_id = v.id
			' . $whereSql . '
			GROUP BY v.id, v.data_venda, c.nome, f.nome, v.valor_total
			' . $orderSql;

		$stmt = $this->conn->prepare($sql);
		$this->bindFiltros($stmt, $params);
		$stmt->execute();

		return $stmt->fetchAll();
	}

	private function buildFiltrosSql(array $filtros): array
	{
		$where = [];
		$params = [];

		$vendaId = (int) ($filtros['venda_id'] ?? 0);
		if ($vendaId > 0) {
			$where[] = 'v.id = :venda_id';
			$params[':venda_id'] = $vendaId;
		}

		$dataInicio = trim((string) ($filtros['data_inicio'] ?? ''));
		if ($dataInicio !== '') {
			$where[] = 'DATE(v.data_venda) >= :data_inicio';
			$params[':data_inicio'] = $dataInicio;
		}

		$dataFim = trim((string) ($filtros['data_fim'] ?? ''));
		if ($dataFim !== '') {
			$where[] = 'DATE(v.data_venda) <= :data_fim';
			$params[':data_fim'] = $dataFim;
		}

		$clienteId = (int) ($filtros['cliente_id'] ?? 0);
		if ($clienteId > 0) {
			$where[] = 'v.cliente_id = :cliente_id';
			$params[':cliente_id'] = $clienteId;
		}

		$funcionarioId = (int) ($filtros['funcionario_id'] ?? 0);
		if ($funcionarioId > 0) {
			$where[] = 'v.funcionario_id = :funcionario_id';
			$params[':funcionario_id'] = $funcionarioId;
		}

		if (empty($where)) {
			return ['', $params];
		}

		return ['WHERE ' . implode(' AND ', $where), $params];
	}

	private function bindFiltros(\PDOStatement $stmt, array $params): void
	{
		foreach ($params as $key => $value) {
			if (is_int($value)) {
				$stmt->bindValue($key, $value, PDO::PARAM_INT);
				continue;
			}

			$stmt->bindValue($key, (string) $value, PDO::PARAM_STR);
		}
	}

	private function buildOrderSql(string $ordem): string
	{
		if ($ordem === 'data_asc') {
			return 'ORDER BY v.data_venda ASC, v.id ASC';
		}

		if ($ordem === 'total_desc') {
			return 'ORDER BY v.valor_total DESC, v.data_venda DESC';
		}

		if ($ordem === 'total_asc') {
			return 'ORDER BY v.valor_total ASC, v.data_venda DESC';
		}

		return 'ORDER BY v.data_venda DESC, v.id DESC';
	}

	private function buscarItemVenda(int $itemId): ?array
	{
		$sql = 'SELECT * FROM itens_venda WHERE id = :id';
		$stmt = $this->conn->prepare($sql);
		$stmt->bindValue(':id', $itemId, PDO::PARAM_INT);
		$stmt->execute();

		$item = $stmt->fetch();
		return $item ?: null;
	}

	private function recalcularTotalVenda(int $vendaId): void
	{
		$sql = 'UPDATE vendas v
				SET v.valor_total = (
					SELECT COALESCE(SUM(iv.subtotal), 0)
					FROM itens_venda iv
					WHERE iv.venda_id = v.id
				)
				WHERE v.id = :venda_id';

		$stmt = $this->conn->prepare($sql);
		$stmt->bindValue(':venda_id', $vendaId, PDO::PARAM_INT);
		$stmt->execute();
	}
}

<?php

namespace App\DAO;

use Exception;
use PDO;
use PDOStatement;

class VendaDAO
{
	private PDO $conn;

	public function __construct()
	{
		$this->conn = Connection::getConn();
	}

	public function criarVenda(?string $clienteCpf, string $funcionarioCpf): int
	{
		$sql = "INSERT INTO Venda (data, cpf_cliente, cpf_funcionario, valor_total, status)
				VALUES (NOW(), :cpf_cliente, :cpf_funcionario, 0, 'aberta')";

		$stmt = $this->conn->prepare($sql);
		$stmt->bindValue(':cpf_cliente', $clienteCpf, $clienteCpf === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
		$stmt->bindValue(':cpf_funcionario', $funcionarioCpf, PDO::PARAM_STR);
		$stmt->execute();

		return (int) $this->conn->lastInsertId();
	}

	public function buscarVenda(int $vendaId): ?array
	{
		$sql = 'SELECT id_venda AS id,
					   data AS data_venda,
					   valor_total,
					   cpf_cliente AS cliente_id,
					   cpf_funcionario AS funcionario_id,
					   status,
					   finalizada_em,
					   cancelada_em
				FROM Venda
				WHERE id_venda = :id_venda';

		$stmt = $this->conn->prepare($sql);
		$stmt->bindValue(':id_venda', $vendaId, PDO::PARAM_INT);
		$stmt->execute();

		$venda = $stmt->fetch();

		return $venda ?: null;
	}

	public function listarItensDaVenda(int $vendaId): array
	{
		$sql = 'SELECT iv.id_venda AS venda_id,
					   iv.cod_barras AS produto_id,
					   iv.lote AS lote_id,
					   iv.quantidade,
					   iv.preco_venda AS preco_unitario_momento,
					   (iv.quantidade * iv.preco_venda) AS subtotal,
					   iv.id_receita AS receita_id,
					   p.nome AS produto_nome,
					   iv.lote AS numero_lote,
					   e.data_validade AS validade
				FROM Item_Venda iv
				INNER JOIN Produto p ON p.cod_barras = iv.cod_barras
				INNER JOIN Estoque e ON e.cod_barras = iv.cod_barras AND e.lote = iv.lote
				WHERE iv.id_venda = :id_venda
				ORDER BY iv.cod_barras ASC, iv.lote ASC';

		$stmt = $this->conn->prepare($sql);
		$stmt->bindValue(':id_venda', $vendaId, PDO::PARAM_INT);
		$stmt->execute();

		return $stmt->fetchAll();
	}

	public function adicionarItemFefo(int $vendaId, string $codBarras, int $quantidade, ?int $receitaId = null): float
	{
		if ($quantidade <= 0) {
			throw new Exception('Quantidade deve ser maior que zero.');
		}

		$this->conn->beginTransaction();

		try {
			$venda = $this->buscarVendaParaUpdate($vendaId);
			if ($venda === null) {
				throw new Exception('Venda nao encontrada.');
			}

			if ((string) $venda['status'] !== 'aberta') {
				throw new Exception('Apenas vendas abertas podem receber itens.');
			}

			$produto = $this->buscarProdutoAtivoParaUpdate($codBarras);
			if ($produto === null) {
				throw new Exception('Produto nao encontrado.');
			}

			$cpfCliente = $venda['cliente_id'] !== null ? (string) $venda['cliente_id'] : null;
			$this->validarReceitaSeNecessario($produto, $cpfCliente, $codBarras, $receitaId);

			$lotes = $this->buscarLotesFefoParaUpdate($codBarras);
			$restante = $quantidade;
			$precoUnitario = (float) $produto['preco_atual'];
			$totalAdicionado = 0.0;

			foreach ($lotes as $lote) {
				if ($restante <= 0) {
					break;
				}

				$saldoAtual = (int) $lote['quantidade_disponivel'];
				$consumir = min($restante, $saldoAtual);
				if ($consumir <= 0) {
					continue;
				}

				$numeroLote = (string) $lote['lote'];
				$this->atualizarSaldoEstoque($codBarras, $numeroLote, $saldoAtual - $consumir);

				$itemAtual = $this->buscarItemVendaParaUpdate($vendaId, $codBarras, $numeroLote);
				if ($itemAtual === null) {
					$insertItem = $this->conn->prepare('INSERT INTO Item_Venda
						(id_venda, cod_barras, lote, quantidade, preco_venda, id_receita)
						VALUES
						(:id_venda, :cod_barras, :lote, :quantidade, :preco_venda, :id_receita)');
					$insertItem->bindValue(':id_venda', $vendaId, PDO::PARAM_INT);
					$insertItem->bindValue(':cod_barras', $codBarras, PDO::PARAM_STR);
					$insertItem->bindValue(':lote', $numeroLote, PDO::PARAM_STR);
					$insertItem->bindValue(':quantidade', $consumir, PDO::PARAM_INT);
					$insertItem->bindValue(':preco_venda', $precoUnitario);
					$insertItem->bindValue(':id_receita', $receitaId, $receitaId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
					$insertItem->execute();
				} else {
					$novaQtd = (int) $itemAtual['quantidade'] + $consumir;
					$updateItem = $this->conn->prepare('UPDATE Item_Venda
						SET quantidade = :quantidade,
							preco_venda = :preco_venda,
							id_receita = :id_receita
						WHERE id_venda = :id_venda AND cod_barras = :cod_barras AND lote = :lote');
					$updateItem->bindValue(':quantidade', $novaQtd, PDO::PARAM_INT);
					$updateItem->bindValue(':preco_venda', $precoUnitario);
					$updateItem->bindValue(':id_receita', $receitaId, $receitaId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
					$updateItem->bindValue(':id_venda', $vendaId, PDO::PARAM_INT);
					$updateItem->bindValue(':cod_barras', $codBarras, PDO::PARAM_STR);
					$updateItem->bindValue(':lote', $numeroLote, PDO::PARAM_STR);
					$updateItem->execute();
				}

				$restante -= $consumir;
				$totalAdicionado += $consumir * $precoUnitario;
			}

			if ($restante > 0) {
				throw new Exception('Estoque insuficiente para atender a quantidade solicitada.');
			}

			$this->recalcularTotalVenda($vendaId);
			$this->conn->commit();

			return $totalAdicionado;
		} catch (Exception $e) {
			$this->conn->rollBack();
			throw $e;
		}
	}

	public function atualizarQuantidadeItem(int $vendaId, string $codBarras, string $lote, int $novaQuantidade): void
	{
		if ($novaQuantidade <= 0) {
			throw new Exception('Quantidade deve ser maior que zero.');
		}

		$this->conn->beginTransaction();

		try {
			$item = $this->buscarItemVendaParaUpdate($vendaId, $codBarras, $lote);
			if ($item === null) {
				throw new Exception('Item da venda nao encontrado.');
			}

			$quantidadeAtual = (int) $item['quantidade'];
			$delta = $novaQuantidade - $quantidadeAtual;
			if ($delta === 0) {
				$this->conn->commit();
				return;
			}

			$estoque = $this->buscarEstoqueParaUpdate($codBarras, $lote);
			if ($estoque === null) {
				throw new Exception('Lote do item nao encontrado.');
			}

			$saldoAtual = (int) $estoque['quantidade_disponivel'];
			$novoSaldo = $saldoAtual - $delta;
			if ($novoSaldo < 0) {
				throw new Exception('Estoque insuficiente no lote para aumentar a quantidade.');
			}

			$this->atualizarSaldoEstoque($codBarras, $lote, $novoSaldo);

			$updateItem = $this->conn->prepare('UPDATE Item_Venda
				SET quantidade = :quantidade
				WHERE id_venda = :id_venda AND cod_barras = :cod_barras AND lote = :lote');
			$updateItem->bindValue(':quantidade', $novaQuantidade, PDO::PARAM_INT);
			$updateItem->bindValue(':id_venda', $vendaId, PDO::PARAM_INT);
			$updateItem->bindValue(':cod_barras', $codBarras, PDO::PARAM_STR);
			$updateItem->bindValue(':lote', $lote, PDO::PARAM_STR);
			$updateItem->execute();

			$this->recalcularTotalVenda($vendaId);
			$this->conn->commit();
		} catch (Exception $e) {
			$this->conn->rollBack();
			throw $e;
		}
	}

	public function removerItem(int $vendaId, string $codBarras, string $lote): void
	{
		$this->conn->beginTransaction();

		try {
			$item = $this->buscarItemVendaParaUpdate($vendaId, $codBarras, $lote);
			if ($item === null) {
				throw new Exception('Item da venda nao encontrado.');
			}

			$estoque = $this->buscarEstoqueParaUpdate($codBarras, $lote);
			if ($estoque === null) {
				throw new Exception('Lote do item nao encontrado.');
			}

			$novoSaldo = (int) $estoque['quantidade_disponivel'] + (int) $item['quantidade'];
			$this->atualizarSaldoEstoque($codBarras, $lote, $novoSaldo);

			$deleteItem = $this->conn->prepare('DELETE FROM Item_Venda
				WHERE id_venda = :id_venda AND cod_barras = :cod_barras AND lote = :lote');
			$deleteItem->bindValue(':id_venda', $vendaId, PDO::PARAM_INT);
			$deleteItem->bindValue(':cod_barras', $codBarras, PDO::PARAM_STR);
			$deleteItem->bindValue(':lote', $lote, PDO::PARAM_STR);
			$deleteItem->execute();

			$this->recalcularTotalVenda($vendaId);
			$this->conn->commit();
		} catch (Exception $e) {
			$this->conn->rollBack();
			throw $e;
		}
	}

	public function finalizarVenda(int $vendaId): void
	{
		$this->conn->beginTransaction();

		try {
			$venda = $this->buscarVendaParaUpdate($vendaId);
			if ($venda === null) {
				throw new Exception('Venda nao encontrada.');
			}

			if ((string) $venda['status'] !== 'aberta') {
				throw new Exception('Somente vendas abertas podem ser finalizadas.');
			}

			$itens = $this->listarItensDaVenda($vendaId);
			if (empty($itens)) {
				throw new Exception('Adicione ao menos um item antes de finalizar a venda.');
			}

			$this->recalcularTotalVenda($vendaId);

			$idsReceita = [];
			foreach ($itens as $item) {
				if ($item['receita_id'] !== null) {
					$idsReceita[(int) $item['receita_id']] = true;
				}
			}

			if (!empty($idsReceita)) {
				$insertUso = $this->conn->prepare('INSERT INTO Uso_Receita (id_receita, id_venda) VALUES (:id_receita, :id_venda)');
				foreach (array_keys($idsReceita) as $idReceita) {
					$insertUso->bindValue(':id_receita', $idReceita, PDO::PARAM_INT);
					$insertUso->bindValue(':id_venda', $vendaId, PDO::PARAM_INT);
					$insertUso->execute();
				}
			}

			$finalizar = $this->conn->prepare("UPDATE Venda
				SET status = 'finalizada', finalizada_em = NOW()
				WHERE id_venda = :id_venda");
			$finalizar->bindValue(':id_venda', $vendaId, PDO::PARAM_INT);
			$finalizar->execute();

			$this->conn->commit();
		} catch (Exception $e) {
			$this->conn->rollBack();
			throw $e;
		}
	}

	public function cancelarVenda(int $vendaId): void
	{
		$this->conn->beginTransaction();

		try {
			$venda = $this->buscarVendaParaUpdate($vendaId);
			if ($venda === null) {
				throw new Exception('Venda nao encontrada.');
			}

			if ((string) $venda['status'] === 'cancelada') {
				throw new Exception('Venda ja esta cancelada.');
			}

			$stmtItens = $this->conn->prepare('SELECT cod_barras, lote, quantidade
				FROM Item_Venda
				WHERE id_venda = :id_venda
				FOR UPDATE');
			$stmtItens->bindValue(':id_venda', $vendaId, PDO::PARAM_INT);
			$stmtItens->execute();
			$itens = $stmtItens->fetchAll();

			foreach ($itens as $item) {
				$codBarras = (string) $item['cod_barras'];
				$lote = (string) $item['lote'];
				$quantidade = (int) $item['quantidade'];

				$estoque = $this->buscarEstoqueParaUpdate($codBarras, $lote);
				if ($estoque === null) {
					throw new Exception('Lote do item nao encontrado para cancelamento.');
				}

				$novoSaldo = (int) $estoque['quantidade_disponivel'] + $quantidade;
				$this->atualizarSaldoEstoque($codBarras, $lote, $novoSaldo);
			}

			$deleteUsoReceita = $this->conn->prepare('DELETE FROM Uso_Receita WHERE id_venda = :id_venda');
			$deleteUsoReceita->bindValue(':id_venda', $vendaId, PDO::PARAM_INT);
			$deleteUsoReceita->execute();

			$cancelar = $this->conn->prepare("UPDATE Venda
				SET status = 'cancelada', cancelada_em = NOW()
				WHERE id_venda = :id_venda");
			$cancelar->bindValue(':id_venda', $vendaId, PDO::PARAM_INT);
			$cancelar->execute();

			$this->conn->commit();
		} catch (Exception $e) {
			$this->conn->rollBack();
			throw $e;
		}
	}

	public function sincronizarQuantidadesParaFinalizacao(int $vendaId, array $quantidades): void
	{
		$venda = $this->buscarVenda($vendaId);
		if ($venda === null) {
			throw new Exception('Venda nao encontrada.');
		}

		$this->conn->beginTransaction();

		try {
			$stmtItens = $this->conn->prepare('SELECT id_venda, cod_barras, lote, quantidade
				FROM Item_Venda
				WHERE id_venda = :id_venda
				FOR UPDATE');
			$stmtItens->bindValue(':id_venda', $vendaId, PDO::PARAM_INT);
			$stmtItens->execute();
			$itens = $stmtItens->fetchAll();

			$itensMap = [];
			foreach ($itens as $item) {
				$chave = $this->montarItemKey((string) $item['cod_barras'], (string) $item['lote']);
				$itensMap[$chave] = $item;
			}

			foreach ($quantidades as $itemKeyRaw => $novaQtdRaw) {
				$itemKey = trim((string) $itemKeyRaw);
				$novaQuantidade = (int) $novaQtdRaw;

				if ($itemKey === '' || !isset($itensMap[$itemKey])) {
					throw new Exception('Item invalido para finalizar a venda.');
				}

				if ($novaQuantidade <= 0) {
					throw new Exception('Quantidade deve ser maior que zero para todos os itens.');
				}

				$itemAtual = $itensMap[$itemKey];
				$codBarras = (string) $itemAtual['cod_barras'];
				$lote = (string) $itemAtual['lote'];
				$quantidadeAtual = (int) $itemAtual['quantidade'];
				$delta = $novaQuantidade - $quantidadeAtual;

				if ($delta === 0) {
					continue;
				}

				$estoque = $this->buscarEstoqueParaUpdate($codBarras, $lote);
				if ($estoque === null) {
					throw new Exception('Lote do item nao encontrado.');
				}

				$saldoAtual = (int) $estoque['quantidade_disponivel'];
				$novoSaldo = $saldoAtual - $delta;
				if ($novoSaldo < 0) {
					throw new Exception('Estoque insuficiente para atualizar quantidades antes da finalizacao.');
				}

				$this->atualizarSaldoEstoque($codBarras, $lote, $novoSaldo);

				$updateItem = $this->conn->prepare('UPDATE Item_Venda
					SET quantidade = :quantidade
					WHERE id_venda = :id_venda AND cod_barras = :cod_barras AND lote = :lote');
				$updateItem->bindValue(':quantidade', $novaQuantidade, PDO::PARAM_INT);
				$updateItem->bindValue(':id_venda', $vendaId, PDO::PARAM_INT);
				$updateItem->bindValue(':cod_barras', $codBarras, PDO::PARAM_STR);
				$updateItem->bindValue(':lote', $lote, PDO::PARAM_STR);
				$updateItem->execute();
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

		$sql = 'SELECT v.id_venda AS id,
				v.data AS data_venda,
				v.valor_total,
				v.status,
				v.cpf_cliente AS cliente_id,
				v.cpf_funcionario AS funcionario_id,
				c.nome AS cliente_nome,
				f.nome AS funcionario_nome,
				COUNT(iv.cod_barras) AS total_itens
			FROM Venda v
			LEFT JOIN Cliente c ON c.cpf = v.cpf_cliente
			INNER JOIN Funcionario f ON f.cpf = v.cpf_funcionario
			LEFT JOIN Item_Venda iv ON iv.id_venda = v.id_venda
			' . $whereSql . '
			GROUP BY v.id_venda, v.data, v.valor_total, v.status, v.cpf_cliente, v.cpf_funcionario, c.nome, f.nome
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

		$sql = 'SELECT iv.id_venda AS venda_id,
				iv.cod_barras AS produto_id,
				iv.lote AS lote_id,
				iv.quantidade,
				iv.preco_venda AS preco_unitario_momento,
				(iv.quantidade * iv.preco_venda) AS subtotal,
				iv.id_receita AS receita_id,
				p.nome AS produto_nome,
				iv.lote AS numero_lote,
				e.data_validade AS validade
			FROM Item_Venda iv
			INNER JOIN Produto p ON p.cod_barras = iv.cod_barras
			INNER JOIN Estoque e ON e.cod_barras = iv.cod_barras AND e.lote = iv.lote
			WHERE iv.id_venda IN (' . $placeholders . ')
			ORDER BY iv.id_venda DESC, iv.cod_barras ASC, iv.lote ASC';

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

		$sql = 'SELECT v.id_venda AS venda_id,
				v.data AS data_venda,
				COALESCE(c.nome, "Sem cliente") AS cliente_nome,
				f.nome AS funcionario_nome,
				v.valor_total,
				CONCAT(iv.cod_barras, "|", iv.lote) AS item_id,
				p.nome AS produto_nome,
				iv.lote AS numero_lote,
				e.data_validade AS validade,
				iv.quantidade,
				iv.preco_venda AS preco_unitario_momento,
				(iv.quantidade * iv.preco_venda) AS subtotal,
				iv.id_receita AS receita_id
			FROM Venda v
			LEFT JOIN Cliente c ON c.cpf = v.cpf_cliente
			INNER JOIN Funcionario f ON f.cpf = v.cpf_funcionario
			LEFT JOIN Item_Venda iv ON iv.id_venda = v.id_venda
			LEFT JOIN Produto p ON p.cod_barras = iv.cod_barras
			LEFT JOIN Estoque e ON e.cod_barras = iv.cod_barras AND e.lote = iv.lote
			' . $whereSql . '
			' . $orderSql . ', iv.cod_barras ASC, iv.lote ASC';

		$stmt = $this->conn->prepare($sql);
		$this->bindFiltros($stmt, $params);
		$stmt->execute();

		return $stmt->fetchAll();
	}

	public function listarParaExportacaoResumo(array $filtros = [], string $ordem = 'data_desc'): array
	{
		[$whereSql, $params] = $this->buildFiltrosSql($filtros);
		$orderSql = $this->buildOrderSql($ordem);

		$sql = 'SELECT v.id_venda AS venda_id,
				v.data AS data_venda,
				COALESCE(c.nome, "Sem cliente") AS cliente_nome,
				f.nome AS funcionario_nome,
				COUNT(iv.cod_barras) AS total_itens,
				v.valor_total
			FROM Venda v
			LEFT JOIN Cliente c ON c.cpf = v.cpf_cliente
			INNER JOIN Funcionario f ON f.cpf = v.cpf_funcionario
			LEFT JOIN Item_Venda iv ON iv.id_venda = v.id_venda
			' . $whereSql . '
			GROUP BY v.id_venda, v.data, c.nome, f.nome, v.valor_total
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
			$where[] = 'v.id_venda = :venda_id';
			$params[':venda_id'] = $vendaId;
		}

		$dataInicio = trim((string) ($filtros['data_inicio'] ?? ''));
		if ($dataInicio !== '') {
			$where[] = 'DATE(v.data) >= :data_inicio';
			$params[':data_inicio'] = $dataInicio;
		}

		$dataFim = trim((string) ($filtros['data_fim'] ?? ''));
		if ($dataFim !== '') {
			$where[] = 'DATE(v.data) <= :data_fim';
			$params[':data_fim'] = $dataFim;
		}

		$clienteCpf = trim((string) ($filtros['cliente_id'] ?? ''));
		if ($clienteCpf !== '') {
			$where[] = 'v.cpf_cliente = :cliente_id';
			$params[':cliente_id'] = $clienteCpf;
		}

		$funcionarioCpf = trim((string) ($filtros['funcionario_id'] ?? ''));
		if ($funcionarioCpf !== '') {
			$where[] = 'v.cpf_funcionario = :funcionario_id';
			$params[':funcionario_id'] = $funcionarioCpf;
		}

		if (empty($where)) {
			return ['', $params];
		}

		return ['WHERE ' . implode(' AND ', $where), $params];
	}

	private function bindFiltros(PDOStatement $stmt, array $params): void
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
			return 'ORDER BY v.data ASC, v.id_venda ASC';
		}

		if ($ordem === 'total_desc') {
			return 'ORDER BY v.valor_total DESC, v.data DESC';
		}

		if ($ordem === 'total_asc') {
			return 'ORDER BY v.valor_total ASC, v.data DESC';
		}

		return 'ORDER BY v.data DESC, v.id_venda DESC';
	}

	private function buscarVendaParaUpdate(int $vendaId): ?array
	{
		$sql = 'SELECT id_venda AS id,
					   cpf_cliente AS cliente_id,
					   cpf_funcionario AS funcionario_id,
					   status,
					   valor_total
				FROM Venda
				WHERE id_venda = :id_venda
				FOR UPDATE';

		$stmt = $this->conn->prepare($sql);
		$stmt->bindValue(':id_venda', $vendaId, PDO::PARAM_INT);
		$stmt->execute();

		$venda = $stmt->fetch();
		return $venda ?: null;
	}

	private function buscarProdutoAtivoParaUpdate(string $codBarras): ?array
	{
		$sql = 'SELECT cod_barras,
					   COALESCE(precisa_receita, 0) AS exige_receita,
					   preco AS preco_atual
				FROM Produto
				WHERE cod_barras = :cod_barras
				  AND ativo = 1
				FOR UPDATE';

		$stmt = $this->conn->prepare($sql);
		$stmt->bindValue(':cod_barras', $codBarras, PDO::PARAM_STR);
		$stmt->execute();

		$produto = $stmt->fetch();
		return $produto ?: null;
	}

	private function validarReceitaSeNecessario(array $produto, ?string $cpfCliente, string $codBarras, ?int $receitaId): void
	{
		if ((int) $produto['exige_receita'] !== 1) {
			return;
		}

		if ($receitaId === null) {
			throw new Exception('Produto exige receita.');
		}

		if ($cpfCliente === null || $cpfCliente === '') {
			throw new Exception('Venda de controlado exige cliente vinculado.');
		}

		$receitaSql = 'SELECT 1
					   FROM Receita r
					   INNER JOIN Item_Receita ir ON ir.id_receita = r.id_receita
					   LEFT JOIN Uso_Receita ur ON ur.id_receita = r.id_receita
					   WHERE r.id_receita = :id_receita
						 AND r.cpf_cliente = :cpf_cliente
						 AND ir.cod_barras = :cod_barras
						 AND ur.id_receita IS NULL
					   LIMIT 1';

		$receitaStmt = $this->conn->prepare($receitaSql);
		$receitaStmt->bindValue(':id_receita', $receitaId, PDO::PARAM_INT);
		$receitaStmt->bindValue(':cpf_cliente', $cpfCliente, PDO::PARAM_STR);
		$receitaStmt->bindValue(':cod_barras', $codBarras, PDO::PARAM_STR);
		$receitaStmt->execute();

		if ($receitaStmt->fetchColumn() === false) {
			throw new Exception('Receita invalida para o cliente/produto ou ja utilizada.');
		}
	}

	private function buscarLotesFefoParaUpdate(string $codBarras): array
	{
		$sql = 'SELECT cod_barras, lote, quantidade_disponivel
				FROM Estoque
				WHERE cod_barras = :cod_barras
				  AND quantidade_disponivel > 0
				  AND data_validade >= CURDATE()
				ORDER BY data_validade ASC, lote ASC
				FOR UPDATE';

		$stmt = $this->conn->prepare($sql);
		$stmt->bindValue(':cod_barras', $codBarras, PDO::PARAM_STR);
		$stmt->execute();

		return $stmt->fetchAll();
	}

	private function buscarItemVendaParaUpdate(int $vendaId, string $codBarras, string $lote): ?array
	{
		$sql = 'SELECT id_venda, cod_barras, lote, quantidade, preco_venda, id_receita
				FROM Item_Venda
				WHERE id_venda = :id_venda
				  AND cod_barras = :cod_barras
				  AND lote = :lote
				FOR UPDATE';

		$stmt = $this->conn->prepare($sql);
		$stmt->bindValue(':id_venda', $vendaId, PDO::PARAM_INT);
		$stmt->bindValue(':cod_barras', $codBarras, PDO::PARAM_STR);
		$stmt->bindValue(':lote', $lote, PDO::PARAM_STR);
		$stmt->execute();

		$item = $stmt->fetch();
		return $item ?: null;
	}

	private function buscarEstoqueParaUpdate(string $codBarras, string $lote): ?array
	{
		$sql = 'SELECT cod_barras, lote, quantidade_disponivel
				FROM Estoque
				WHERE cod_barras = :cod_barras AND lote = :lote
				FOR UPDATE';

		$stmt = $this->conn->prepare($sql);
		$stmt->bindValue(':cod_barras', $codBarras, PDO::PARAM_STR);
		$stmt->bindValue(':lote', $lote, PDO::PARAM_STR);
		$stmt->execute();

		$estoque = $stmt->fetch();
		return $estoque ?: null;
	}

	private function atualizarSaldoEstoque(string $codBarras, string $lote, int $novoSaldo): void
	{
		$updateLote = $this->conn->prepare('UPDATE Estoque
			SET quantidade_disponivel = :saldo
			WHERE cod_barras = :cod_barras AND lote = :lote');
		$updateLote->bindValue(':saldo', $novoSaldo, PDO::PARAM_INT);
		$updateLote->bindValue(':cod_barras', $codBarras, PDO::PARAM_STR);
		$updateLote->bindValue(':lote', $lote, PDO::PARAM_STR);
		$updateLote->execute();
	}

	private function montarItemKey(string $codBarras, string $lote): string
	{
		return $codBarras . '|' . $lote;
	}

	private function recalcularTotalVenda(int $vendaId): void
	{
		$sql = 'UPDATE Venda v
				SET v.valor_total = (
					SELECT COALESCE(SUM(iv.quantidade * iv.preco_venda), 0)
					FROM Item_Venda iv
					WHERE iv.id_venda = v.id_venda
				)
				WHERE v.id_venda = :id_venda';

		$stmt = $this->conn->prepare($sql);
		$stmt->bindValue(':id_venda', $vendaId, PDO::PARAM_INT);
		$stmt->execute();
	}
}

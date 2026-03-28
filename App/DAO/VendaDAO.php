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
}

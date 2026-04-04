<?php

namespace App\DAO;

use PDO;

class ReceitaDAO
{
	private PDO $conn;

	public function __construct()
	{
		$this->conn = Connection::getConn();
	}

	public function listarPorCliente(int $clienteId): array
	{
		$sql = 'SELECT * FROM receitas WHERE cliente_id = :cliente_id ORDER BY data_receita DESC';
		$stmt = $this->conn->prepare($sql);
		$stmt->bindValue(':cliente_id', $clienteId, PDO::PARAM_INT);
		$stmt->execute();

		return $stmt->fetchAll();
	}

	public function listarComCliente(): array
	{
		$sql = 'SELECT r.*, c.nome AS cliente_nome
				FROM receitas r
				INNER JOIN clientes c ON c.id = r.cliente_id
				ORDER BY r.data_receita DESC, r.id DESC';
		$stmt = $this->conn->query($sql);

		return $stmt->fetchAll();
	}

	public function listarValidasParaClienteProduto(int $clienteId, int $produtoId): array
	{
		$sql = 'SELECT r.id, r.data_receita, r.medico_nome, r.crm
				FROM receitas r
				INNER JOIN receita_itens ri ON ri.receita_id = r.id
				WHERE r.cliente_id = :cliente_id
				  AND ri.produto_id = :produto_id
				ORDER BY r.data_receita DESC, r.id DESC';
		$stmt = $this->conn->prepare($sql);
		$stmt->bindValue(':cliente_id', $clienteId, PDO::PARAM_INT);
		$stmt->bindValue(':produto_id', $produtoId, PDO::PARAM_INT);
		$stmt->execute();

		return $stmt->fetchAll();
	}

	public function criarComItens(array $data, array $itens): int
	{
		$this->conn->beginTransaction();

		try {
			$sqlReceita = 'INSERT INTO receitas (cliente_id, medico_nome, crm, data_receita, observacoes)
						  VALUES (:cliente_id, :medico_nome, :crm, :data_receita, :observacoes)';
			$stmtReceita = $this->conn->prepare($sqlReceita);
			$stmtReceita->bindValue(':cliente_id', (int) $data['cliente_id'], PDO::PARAM_INT);
			$stmtReceita->bindValue(':medico_nome', $data['medico_nome']);
			$stmtReceita->bindValue(':crm', $data['crm']);
			$stmtReceita->bindValue(':data_receita', $data['data_receita']);
			$stmtReceita->bindValue(':observacoes', $data['observacoes'] ?: null);
			$stmtReceita->execute();

			$receitaId = (int) $this->conn->lastInsertId();

			$sqlItem = 'INSERT INTO receita_itens (receita_id, produto_id, posologia)
					VALUES (:receita_id, :produto_id, :posologia)';
			$stmtItem = $this->conn->prepare($sqlItem);

			foreach ($itens as $item) {
				$stmtItem->bindValue(':receita_id', $receitaId, PDO::PARAM_INT);
				$stmtItem->bindValue(':produto_id', (int) $item['produto_id'], PDO::PARAM_INT);
				$stmtItem->bindValue(':posologia', $item['posologia'] ?: null);
				$stmtItem->execute();
			}

			$this->conn->commit();

			return $receitaId;
		} catch (\Throwable $e) {
			$this->conn->rollBack();
			throw $e;
		}
	}
}

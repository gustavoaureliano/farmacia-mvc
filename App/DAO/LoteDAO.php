<?php

namespace App\DAO;

use PDO;

class LoteDAO
{
	private PDO $conn;

	public function __construct()
	{
		$this->conn = Connection::getConn();
	}

	public function criar(array $data): int
	{
		$sql = 'INSERT INTO lotes_estoque (produto_id, numero_lote, validade, quantidade_disponivel, localizacao)
				VALUES (:produto_id, :numero_lote, :validade, :quantidade_disponivel, :localizacao)';

		$stmt = $this->conn->prepare($sql);
		$stmt->bindValue(':produto_id', (int) $data['produto_id'], PDO::PARAM_INT);
		$stmt->bindValue(':numero_lote', $data['numero_lote']);
		$stmt->bindValue(':validade', $data['validade']);
		$stmt->bindValue(':quantidade_disponivel', (int) $data['quantidade_disponivel'], PDO::PARAM_INT);
		$stmt->bindValue(':localizacao', $data['localizacao'] ?: null);
		$stmt->execute();

		return (int) $this->conn->lastInsertId();
	}

	public function listar(): array
	{
		$sql = 'SELECT le.*, p.nome AS produto_nome
				FROM lotes_estoque le
				INNER JOIN produtos p ON p.id = le.produto_id
				ORDER BY le.validade ASC, le.id ASC';
		$stmt = $this->conn->query($sql);

		return $stmt->fetchAll();
	}

	public function resumoEstoquePorProduto(): array
	{
		$sql = 'SELECT p.id AS produto_id,
				   p.nome AS produto_nome,
				   COALESCE(SUM(CASE
					   WHEN le.quantidade_disponivel > 0 AND le.validade >= CURDATE() THEN le.quantidade_disponivel
					   ELSE 0
				   END), 0) AS estoque_valido,
				   MIN(CASE
					   WHEN le.quantidade_disponivel > 0 AND le.validade >= CURDATE() THEN le.validade
					   ELSE NULL
				   END) AS proxima_validade,
				   COALESCE(SUM(CASE
					   WHEN le.quantidade_disponivel > 0 AND le.validade >= CURDATE() THEN 1
					   ELSE 0
				   END), 0) AS lotes_validos
				FROM produtos p
				LEFT JOIN lotes_estoque le ON le.produto_id = p.id
				WHERE p.ativo = 1
				GROUP BY p.id, p.nome
				ORDER BY estoque_valido DESC, p.nome ASC';

		$stmt = $this->conn->query($sql);

		return $stmt->fetchAll();
	}
}

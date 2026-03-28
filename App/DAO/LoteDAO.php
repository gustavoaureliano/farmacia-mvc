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
}

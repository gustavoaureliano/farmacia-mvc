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
}

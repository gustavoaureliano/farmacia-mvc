<?php

namespace App\DAO;

use PDO;

class ClienteDAO
{
	private PDO $conn;

	public function __construct()
	{
		$this->conn = Connection::getConn();
	}

	public function listar(): array
	{
		$stmt = $this->conn->query('SELECT * FROM clientes ORDER BY nome ASC');
		return $stmt->fetchAll();
	}

	public function criar(array $data): int
	{
		$sql = 'INSERT INTO clientes (nome, cpf, data_nascimento, telefone)
				VALUES (:nome, :cpf, :data_nascimento, :telefone)';

		$stmt = $this->conn->prepare($sql);
		$stmt->bindValue(':nome', $data['nome']);
		$stmt->bindValue(':cpf', $data['cpf']);
		$stmt->bindValue(':data_nascimento', $data['data_nascimento'] ?: null);
		$stmt->bindValue(':telefone', $data['telefone'] ?: null);
		$stmt->execute();

		return (int) $this->conn->lastInsertId();
	}
}

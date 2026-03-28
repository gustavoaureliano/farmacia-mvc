<?php

namespace App\DAO;

use PDO;

class ProdutoDAO
{
	private PDO $conn;

	public function __construct()
	{
		$this->conn = Connection::getConn();
	}

	public function listar(): array
	{
		$sql = 'SELECT * FROM produtos ORDER BY nome ASC';
		$stmt = $this->conn->query($sql);

		return $stmt->fetchAll();
	}

	public function buscarPorId(int $id): ?array
	{
		$sql = 'SELECT * FROM produtos WHERE id = :id';
		$stmt = $this->conn->prepare($sql);
		$stmt->bindValue(':id', $id, PDO::PARAM_INT);
		$stmt->execute();

		$produto = $stmt->fetch();

		return $produto ?: null;
	}

	public function criar(array $data): int
	{
		$sql = 'INSERT INTO produtos (nome, principio_ativo, marca_laboratorio, tipo, exige_receita, preco_atual, codigo_barras, ativo)
				VALUES (:nome, :principio_ativo, :marca_laboratorio, :tipo, :exige_receita, :preco_atual, :codigo_barras, 1)';

		$stmt = $this->conn->prepare($sql);
		$stmt->bindValue(':nome', $data['nome']);
		$stmt->bindValue(':principio_ativo', $data['principio_ativo']);
		$stmt->bindValue(':marca_laboratorio', $data['marca_laboratorio']);
		$stmt->bindValue(':tipo', $data['tipo']);
		$stmt->bindValue(':exige_receita', (int) $data['exige_receita'], PDO::PARAM_INT);
		$stmt->bindValue(':preco_atual', $data['preco_atual']);
		$stmt->bindValue(':codigo_barras', $data['codigo_barras']);
		$stmt->execute();

		return (int) $this->conn->lastInsertId();
	}
}

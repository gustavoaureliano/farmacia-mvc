<?php

namespace App\DAO;

use PDO;

class FuncionarioDAO
{
	private PDO $conn;

	public function __construct()
	{
		$this->conn = Connection::getConn();
	}

	public function listar(): array
	{
		$stmt = $this->conn->query('SELECT cpf, nome, cargo, registro_profissional AS crf, ativo FROM Funcionario WHERE ativo = 1 ORDER BY nome ASC');
		return $stmt->fetchAll();
	}

	public function criar(array $data): string
	{
		$sql = 'INSERT INTO Funcionario (nome, cargo, cpf, registro_profissional, ativo)
				VALUES (:nome, :cargo, :cpf, :crf, 1)';

		$stmt = $this->conn->prepare($sql);
		$stmt->bindValue(':nome', $data['nome']);
		$stmt->bindValue(':cargo', $data['cargo']);
		$stmt->bindValue(':cpf', $data['cpf']);
		$stmt->bindValue(':crf', $data['crf'] ?: null);
		$stmt->execute();

		return (string) $data['cpf'];
	}
}

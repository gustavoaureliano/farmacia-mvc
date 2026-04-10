<?php

namespace App\DAO;

use DomainException;
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
		$stmt = $this->conn->query('SELECT cpf, nome, data_nascimento, telefone FROM Cliente ORDER BY nome ASC');
		return $stmt->fetchAll();
	}

	public function buscarPorCpf(string $cpf): ?array
	{
		$cpfDigits = $this->cpfDigits($cpf);
		if ($cpfDigits === '') {
			return null;
		}

		$sql = "SELECT cpf, nome, data_nascimento, telefone
				FROM Cliente
				WHERE REPLACE(REPLACE(REPLACE(cpf, '.', ''), '-', ''), ' ', '') = :cpf
				LIMIT 1";

		$stmt = $this->conn->prepare($sql);
		$stmt->bindValue(':cpf', $cpfDigits);
		$stmt->execute();

		$row = $stmt->fetch();
		return $row !== false ? $row : null;
	}

	public function criar(array $data): string
	{
		$sql = 'INSERT INTO Cliente (nome, cpf, data_nascimento, telefone)
				VALUES (:nome, :cpf, :data_nascimento, :telefone)';

		$stmt = $this->conn->prepare($sql);
		$stmt->bindValue(':nome', $data['nome']);
		$stmt->bindValue(':cpf', $data['cpf']);
		$stmt->bindValue(':data_nascimento', $data['data_nascimento'] ?: null);
		$stmt->bindValue(':telefone', $data['telefone'] ?: null);
		$stmt->execute();

		return (string) $data['cpf'];
	}

	public function atualizar(string $cpfOriginal, array $data): void
	{
		$cpfDigits = $this->cpfDigits($cpfOriginal);
		if ($cpfDigits === '') {
			throw new DomainException('CPF invalido.');
		}

		$sql = "UPDATE Cliente
				SET nome = :nome,
					data_nascimento = :data_nascimento,
					telefone = :telefone
				WHERE REPLACE(REPLACE(REPLACE(cpf, '.', ''), '-', ''), ' ', '') = :cpf";

		$stmt = $this->conn->prepare($sql);
		$stmt->bindValue(':nome', $data['nome']);
		$stmt->bindValue(':data_nascimento', $data['data_nascimento'] ?: null);
		$stmt->bindValue(':telefone', $data['telefone'] ?: null);
		$stmt->bindValue(':cpf', $cpfDigits);
		$stmt->execute();

		if ($stmt->rowCount() === 0) {
			throw new DomainException('Cliente nao encontrado para atualizacao.');
		}
	}

	public function excluir(string $cpf): void
	{
		$cpfDigits = $this->cpfDigits($cpf);
		if ($cpfDigits === '') {
			throw new DomainException('CPF invalido.');
		}

		$vinculos = $this->contarVinculos($cpfDigits);
		if (($vinculos['vendas'] ?? 0) > 0 || ($vinculos['receitas'] ?? 0) > 0) {
			throw new DomainException(sprintf(
				'Nao e possivel excluir: existem %d venda(s) e %d receita(s) vinculada(s) a este cliente.',
				(int) ($vinculos['vendas'] ?? 0),
				(int) ($vinculos['receitas'] ?? 0),
			));
		}

		$sql = "DELETE FROM Cliente
				WHERE REPLACE(REPLACE(REPLACE(cpf, '.', ''), '-', ''), ' ', '') = :cpf";
		$stmt = $this->conn->prepare($sql);
		$stmt->bindValue(':cpf', $cpfDigits);
		$stmt->execute();

		if ($stmt->rowCount() === 0) {
			throw new DomainException('Cliente nao encontrado para exclusao.');
		}
	}

	private function contarVinculos(string $cpfDigits): array
	{
		$sqlVenda = "SELECT COUNT(*) FROM Venda
					WHERE cpf_cliente IS NOT NULL
					  AND REPLACE(REPLACE(REPLACE(cpf_cliente, '.', ''), '-', ''), ' ', '') = :cpf";
		$stmtVenda = $this->conn->prepare($sqlVenda);
		$stmtVenda->bindValue(':cpf', $cpfDigits);
		$stmtVenda->execute();
		$vendas = (int) $stmtVenda->fetchColumn();

		$sqlReceita = "SELECT COUNT(*) FROM Receita
					  WHERE REPLACE(REPLACE(REPLACE(cpf_cliente, '.', ''), '-', ''), ' ', '') = :cpf";
		$stmtReceita = $this->conn->prepare($sqlReceita);
		$stmtReceita->bindValue(':cpf', $cpfDigits);
		$stmtReceita->execute();
		$receitas = (int) $stmtReceita->fetchColumn();

		return [
			'vendas' => $vendas,
			'receitas' => $receitas,
		];
	}

	private function cpfDigits(string $cpf): string
	{
		return preg_replace('/\\D+/', '', $cpf) ?: '';
	}
}

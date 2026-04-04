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

	public function listarPorCliente(string $clienteCpf): array
	{
		$sql = 'SELECT r.id_receita AS id,
					   r.data AS data_receita,
					   r.crm_medico AS crm,
					   m.nome AS medico_nome
				FROM Receita r
				INNER JOIN Medico m ON m.crm = r.crm_medico
				WHERE r.cpf_cliente = :cpf_cliente
				ORDER BY r.data DESC, r.id_receita DESC';

		$stmt = $this->conn->prepare($sql);
		$stmt->bindValue(':cpf_cliente', $clienteCpf, PDO::PARAM_STR);
		$stmt->execute();

		return $stmt->fetchAll();
	}

	public function listarComCliente(): array
	{
		$sql = 'SELECT r.id_receita AS id,
					   r.data AS data_receita,
					   r.crm_medico AS crm,
					   r.cpf_cliente AS cliente_id,
					   c.nome AS cliente_nome,
					   m.nome AS medico_nome
				FROM Receita r
				INNER JOIN Cliente c ON c.cpf = r.cpf_cliente
				INNER JOIN Medico m ON m.crm = r.crm_medico
				ORDER BY r.data DESC, r.id_receita DESC';

		$stmt = $this->conn->query($sql);

		return $stmt->fetchAll();
	}

	public function listarValidasParaClienteProduto(string $clienteCpf, string $codBarras): array
	{
		$sql = 'SELECT r.id_receita AS id,
					   r.data AS data_receita,
					   r.crm_medico AS crm,
					   m.nome AS medico_nome
				FROM Receita r
				INNER JOIN Medico m ON m.crm = r.crm_medico
				INNER JOIN Item_Receita ir ON ir.id_receita = r.id_receita
				LEFT JOIN Uso_Receita ur ON ur.id_receita = r.id_receita
				WHERE r.cpf_cliente = :cpf_cliente
				  AND ir.cod_barras = :cod_barras
				  AND ur.id_receita IS NULL
				ORDER BY r.data DESC, r.id_receita DESC';

		$stmt = $this->conn->prepare($sql);
		$stmt->bindValue(':cpf_cliente', $clienteCpf, PDO::PARAM_STR);
		$stmt->bindValue(':cod_barras', $codBarras, PDO::PARAM_STR);
		$stmt->execute();

		return $stmt->fetchAll();
	}

	public function criarComItens(array $data, array $itens): int
	{
		$this->conn->beginTransaction();

		try {
			$sqlMedico = 'INSERT INTO Medico (crm, nome)
				VALUES (:crm, :nome)
				ON DUPLICATE KEY UPDATE nome = VALUES(nome)';
			$stmtMedico = $this->conn->prepare($sqlMedico);
			$stmtMedico->bindValue(':crm', (string) $data['crm']);
			$stmtMedico->bindValue(':nome', (string) $data['medico_nome']);
			$stmtMedico->execute();

			$sqlReceita = 'INSERT INTO Receita (data, crm_medico, cpf_cliente)
				VALUES (:data_receita, :crm_medico, :cpf_cliente)';
			$stmtReceita = $this->conn->prepare($sqlReceita);
			$stmtReceita->bindValue(':data_receita', (string) $data['data_receita']);
			$stmtReceita->bindValue(':crm_medico', (string) $data['crm']);
			$stmtReceita->bindValue(':cpf_cliente', (string) $data['cliente_id']);
			$stmtReceita->execute();

			$receitaId = (int) $this->conn->lastInsertId();

			$sqlItem = 'INSERT INTO Item_Receita (id_receita, cod_barras, quantidade, observacoes)
				VALUES (:id_receita, :cod_barras, :quantidade, :observacoes)';
			$stmtItem = $this->conn->prepare($sqlItem);

			foreach ($itens as $item) {
				$stmtItem->bindValue(':id_receita', $receitaId, PDO::PARAM_INT);
				$stmtItem->bindValue(':cod_barras', (string) $item['produto_id'], PDO::PARAM_STR);
				$stmtItem->bindValue(':quantidade', (int) ($item['quantidade'] ?? 1), PDO::PARAM_INT);
				$stmtItem->bindValue(':observacoes', $item['posologia'] ?: null);
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

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
		$sql = 'INSERT INTO Estoque (cod_barras, lote, data_validade, quantidade_disponivel, localizacao)
				VALUES (:cod_barras, :lote, :data_validade, :quantidade_disponivel, :localizacao)';

		$stmt = $this->conn->prepare($sql);
		$stmt->bindValue(':cod_barras', (string) $data['cod_barras'], PDO::PARAM_STR);
		$stmt->bindValue(':lote', (string) $data['numero_lote'], PDO::PARAM_STR);
		$stmt->bindValue(':data_validade', (string) $data['validade'], PDO::PARAM_STR);
		$stmt->bindValue(':quantidade_disponivel', (int) $data['quantidade_disponivel'], PDO::PARAM_INT);
		$stmt->bindValue(':localizacao', $data['localizacao'] ?: null);
		$stmt->execute();

		return 1;
	}

	public function listar(): array
	{
		$sql = "SELECT e.cod_barras,
					   e.cod_barras AS id,
					   e.lote AS numero_lote,
					   e.data_validade AS validade,
					   e.quantidade_disponivel,
					   e.localizacao,
					   p.nome AS produto_nome
				FROM Estoque e
				INNER JOIN Produto p ON p.cod_barras = e.cod_barras
				ORDER BY e.data_validade ASC, e.lote ASC";
		$stmt = $this->conn->query($sql);

		return $stmt->fetchAll();
	}

	public function resumoEstoquePorProduto(): array
	{
		$sql = 'SELECT p.cod_barras AS cod_barras,
				   p.nome AS produto_nome,
				   COALESCE(SUM(CASE
					   WHEN le.quantidade_disponivel > 0 AND le.data_validade >= CURDATE() THEN le.quantidade_disponivel
					   ELSE 0
				   END), 0) AS estoque_valido,
				   MIN(CASE
					   WHEN le.quantidade_disponivel > 0 AND le.data_validade >= CURDATE() THEN le.data_validade
					   ELSE NULL
				   END) AS proxima_validade,
				   COALESCE(SUM(CASE
					   WHEN le.quantidade_disponivel > 0 AND le.data_validade >= CURDATE() THEN 1
					   ELSE 0
				   END), 0) AS lotes_validos
				FROM Produto p
				LEFT JOIN Estoque le ON le.cod_barras = p.cod_barras
				WHERE p.ativo = 1
				GROUP BY p.cod_barras, p.nome
				ORDER BY estoque_valido DESC, p.nome ASC';

		$stmt = $this->conn->query($sql);

		return $stmt->fetchAll();
	}
}

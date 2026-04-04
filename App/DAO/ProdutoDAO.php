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
		$sql = "SELECT p.cod_barras AS id,
					   p.nome,
					   '' AS principio_ativo,
					   COALESCE(p.marca, '') AS marca_laboratorio,
					   COALESCE(p.tipo, '') AS tipo,
					   COALESCE(p.precisa_receita, 0) AS exige_receita,
					   p.preco AS preco_atual,
					   p.cod_barras AS codigo_barras,
					   p.ativo,
					   COALESCE(est.estoque_disponivel, 0) AS estoque_disponivel
				FROM Produto p
				LEFT JOIN (
					SELECT cod_barras, SUM(quantidade_disponivel) AS estoque_disponivel
					FROM Estoque
					WHERE quantidade_disponivel > 0
					  AND data_validade >= CURDATE()
					GROUP BY cod_barras
				) est ON est.cod_barras = p.cod_barras
				ORDER BY p.nome ASC";
		$stmt = $this->conn->query($sql);

		return $stmt->fetchAll();
	}

	public function buscarPorId(string $codBarras): ?array
	{
		$sql = "SELECT cod_barras AS id,
					   nome,
					   '' AS principio_ativo,
					   COALESCE(marca, '') AS marca_laboratorio,
					   COALESCE(tipo, '') AS tipo,
					   COALESCE(precisa_receita, 0) AS exige_receita,
					   preco AS preco_atual,
					   cod_barras AS codigo_barras,
					   ativo
				FROM Produto
				WHERE cod_barras = :cod_barras";
		$stmt = $this->conn->prepare($sql);
		$stmt->bindValue(':cod_barras', $codBarras, PDO::PARAM_STR);
		$stmt->execute();

		$produto = $stmt->fetch();

		return $produto ?: null;
	}

	public function criar(array $data): string
	{
		$sql = 'INSERT INTO Produto (nome, marca, tipo, precisa_receita, preco, cod_barras, ativo)
				VALUES (:nome, :marca, :tipo, :precisa_receita, :preco, :cod_barras, 1)';

		$stmt = $this->conn->prepare($sql);
		$stmt->bindValue(':nome', $data['nome']);
		$stmt->bindValue(':marca', $data['marca_laboratorio']);
		$stmt->bindValue(':tipo', $data['tipo']);
		$stmt->bindValue(':precisa_receita', (int) $data['exige_receita'], PDO::PARAM_INT);
		$stmt->bindValue(':preco', $data['preco_atual']);
		$stmt->bindValue(':cod_barras', $data['codigo_barras']);
		$stmt->execute();

		return (string) $data['codigo_barras'];
	}

	public function buscar(string $query = '', array $filtros = [], int $limit = 20): array
	{
		$limit = max(1, min($limit, 50));
		$queryOriginal = trim($query);
		$queryNormalizada = $this->normalizarTermoBusca($queryOriginal);

		$where = [];
		$params = [];

		$ativo = $filtros['ativo'] ?? 1;
		if ($ativo !== null && $ativo !== '') {
			$where[] = 'ativo = :ativo';
			$params[':ativo'] = (int) $ativo;
		}

		$tipo = trim((string) ($filtros['tipo'] ?? ''));
		if ($tipo !== '') {
			$where[] = 'tipo = :tipo';
			$params[':tipo'] = $tipo;
		}

		$exigeReceita = $filtros['exige_receita'] ?? '';
		if ($exigeReceita !== '' && ($exigeReceita === 0 || $exigeReceita === 1 || $exigeReceita === '0' || $exigeReceita === '1')) {
			$where[] = 'precisa_receita = :exige_receita';
			$params[':exige_receita'] = (int) $exigeReceita;
		}

		$orderBy = 'ORDER BY nome ASC';

		if ($queryNormalizada !== '') {
			$where[] = '(
				cod_barras = :codigo_barras_exato
				OR nome COLLATE utf8mb4_unicode_ci LIKE :nome_prefixo
				OR nome COLLATE utf8mb4_unicode_ci LIKE :termo_like
				OR marca COLLATE utf8mb4_unicode_ci LIKE :termo_like_marca
				OR tipo COLLATE utf8mb4_unicode_ci LIKE :termo_like_tipo
			)';

			$params[':codigo_barras_exato'] = $queryOriginal;
			$params[':nome_prefixo'] = $queryNormalizada . '%';
			$params[':termo_like'] = '%' . $queryNormalizada . '%';
			$params[':termo_like_marca'] = '%' . $queryNormalizada . '%';
			$params[':termo_like_tipo'] = '%' . $queryNormalizada . '%';

			$orderBy = 'ORDER BY
				CASE
					WHEN cod_barras = :ord_codigo_barras THEN 0
					WHEN nome COLLATE utf8mb4_unicode_ci LIKE :ord_nome_prefixo THEN 1
					WHEN nome COLLATE utf8mb4_unicode_ci LIKE :ord_termo_like THEN 2
					WHEN marca COLLATE utf8mb4_unicode_ci LIKE :ord_termo_like_marca THEN 3
					WHEN tipo COLLATE utf8mb4_unicode_ci LIKE :ord_termo_like_tipo THEN 4
					ELSE 9
				END,
				nome ASC';

			$params[':ord_codigo_barras'] = $queryOriginal;
			$params[':ord_nome_prefixo'] = $queryNormalizada . '%';
			$params[':ord_termo_like'] = '%' . $queryNormalizada . '%';
			$params[':ord_termo_like_marca'] = '%' . $queryNormalizada . '%';
			$params[':ord_termo_like_tipo'] = '%' . $queryNormalizada . '%';
		}

		$sql = "SELECT p.cod_barras AS id,
					   p.nome,
					   '' AS principio_ativo,
					   COALESCE(p.marca, '') AS marca_laboratorio,
					   COALESCE(p.tipo, '') AS tipo,
					   COALESCE(p.precisa_receita, 0) AS exige_receita,
					   p.preco AS preco_atual,
					   p.cod_barras AS codigo_barras,
				COALESCE(est.estoque_disponivel, 0) AS estoque_disponivel
				FROM Produto p
				LEFT JOIN (
					SELECT cod_barras, SUM(quantidade_disponivel) AS estoque_disponivel
					FROM Estoque
					WHERE quantidade_disponivel > 0
					  AND data_validade >= CURDATE()
					GROUP BY cod_barras
				) est ON est.cod_barras = p.cod_barras";

		if (!empty($where)) {
			$sql .= ' WHERE ' . implode(' AND ', $where);
		}

		$sql .= ' ' . $orderBy . ' LIMIT :limite';

		$stmt = $this->conn->prepare($sql);

		foreach ($params as $key => $value) {
			if (is_int($value)) {
				$stmt->bindValue($key, $value, PDO::PARAM_INT);
				continue;
			}

			$stmt->bindValue($key, (string) $value, PDO::PARAM_STR);
		}

		$stmt->bindValue(':limite', $limit, PDO::PARAM_INT);
		$stmt->execute();

		return $stmt->fetchAll();
	}

	private function normalizarTermoBusca(string $termo): string
	{
		$termo = trim($termo);
		if ($termo === '') {
			return '';
		}

		$termo = strtolower($termo);
		$ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $termo);
		if ($ascii !== false) {
			$termo = $ascii;
		}

		$termo = preg_replace('/\s+/', ' ', $termo) ?? $termo;

		return trim($termo);
	}
}

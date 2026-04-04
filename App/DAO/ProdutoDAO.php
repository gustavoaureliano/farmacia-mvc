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
		$sql = 'SELECT p.*, COALESCE(est.estoque_disponivel, 0) AS estoque_disponivel
				FROM produtos p
				LEFT JOIN (
					SELECT produto_id, SUM(quantidade_disponivel) AS estoque_disponivel
					FROM lotes_estoque
					WHERE quantidade_disponivel > 0
					  AND validade >= CURDATE()
					GROUP BY produto_id
				) est ON est.produto_id = p.id
				ORDER BY p.nome ASC';
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

		$tipo = $filtros['tipo'] ?? '';
		if ($tipo !== '' && in_array($tipo, ['generico', 'similar', 'referencia'], true)) {
			$where[] = 'tipo = :tipo';
			$params[':tipo'] = $tipo;
		}

		$exigeReceita = $filtros['exige_receita'] ?? '';
		if ($exigeReceita !== '' && ($exigeReceita === 0 || $exigeReceita === 1 || $exigeReceita === '0' || $exigeReceita === '1')) {
			$where[] = 'exige_receita = :exige_receita';
			$params[':exige_receita'] = (int) $exigeReceita;
		}

		$orderBy = 'ORDER BY nome ASC';

		if ($queryNormalizada !== '') {
			$where[] = '(
				codigo_barras = :codigo_barras_exato
				OR nome COLLATE utf8mb4_unicode_ci LIKE :nome_prefixo
				OR nome COLLATE utf8mb4_unicode_ci LIKE :termo_like
				OR principio_ativo COLLATE utf8mb4_unicode_ci LIKE :termo_like_principio
				OR marca_laboratorio COLLATE utf8mb4_unicode_ci LIKE :termo_like_marca
			)';

			$params[':codigo_barras_exato'] = $queryOriginal;
			$params[':nome_prefixo'] = $queryNormalizada . '%';
			$params[':termo_like'] = '%' . $queryNormalizada . '%';
			$params[':termo_like_principio'] = '%' . $queryNormalizada . '%';
			$params[':termo_like_marca'] = '%' . $queryNormalizada . '%';

			$orderBy = 'ORDER BY
				CASE
					WHEN codigo_barras = :ord_codigo_barras THEN 0
					WHEN nome COLLATE utf8mb4_unicode_ci LIKE :ord_nome_prefixo THEN 1
					WHEN nome COLLATE utf8mb4_unicode_ci LIKE :ord_termo_like THEN 2
					WHEN principio_ativo COLLATE utf8mb4_unicode_ci LIKE :ord_termo_like_principio THEN 3
					WHEN marca_laboratorio COLLATE utf8mb4_unicode_ci LIKE :ord_termo_like_marca THEN 4
					ELSE 9
				END,
				nome ASC';

			$params[':ord_codigo_barras'] = $queryOriginal;
			$params[':ord_nome_prefixo'] = $queryNormalizada . '%';
			$params[':ord_termo_like'] = '%' . $queryNormalizada . '%';
			$params[':ord_termo_like_principio'] = '%' . $queryNormalizada . '%';
			$params[':ord_termo_like_marca'] = '%' . $queryNormalizada . '%';
		}

		$sql = 'SELECT p.id, p.nome, p.principio_ativo, p.marca_laboratorio, p.tipo, p.exige_receita, p.preco_atual, p.codigo_barras,
				COALESCE(est.estoque_disponivel, 0) AS estoque_disponivel
				FROM produtos p
				LEFT JOIN (
					SELECT produto_id, SUM(quantidade_disponivel) AS estoque_disponivel
					FROM lotes_estoque
					WHERE quantidade_disponivel > 0
					  AND validade >= CURDATE()
					GROUP BY produto_id
				) est ON est.produto_id = p.id';

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

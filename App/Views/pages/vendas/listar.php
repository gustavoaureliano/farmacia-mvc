<?php
$filtrosAtuais = is_array($filtros ?? null) ? $filtros : [];
$ordemAtual = is_string($ordem ?? null) ? $ordem : 'data_desc';

$vendaIdAtual = (int) ($filtrosAtuais['venda_id'] ?? 0);
$dataInicio = (string) ($filtrosAtuais['data_inicio'] ?? '');
$dataFim = (string) ($filtrosAtuais['data_fim'] ?? '');
$clienteAtual = (int) ($filtrosAtuais['cliente_id'] ?? 0);
$funcionarioAtual = (int) ($filtrosAtuais['funcionario_id'] ?? 0);

$queryBase = [
	'venda_id' => $vendaIdAtual > 0 ? (string) $vendaIdAtual : '',
	'data_inicio' => $dataInicio,
	'data_fim' => $dataFim,
	'cliente_id' => $clienteAtual > 0 ? (string) $clienteAtual : '',
	'funcionario_id' => $funcionarioAtual > 0 ? (string) $funcionarioAtual : '',
	'ordem' => $ordemAtual,
];

$queryDetalhado = $queryBase;
$queryDetalhado['modo'] = 'detalhado';

$queryResumo = $queryBase;
$queryResumo['modo'] = 'resumo';
?>

<div class="card">
	<div class="section-head">
		<h2>Historico de vendas</h2>
		<div class="pills">
			<a class="btn-inline" href="/vendas/nova">Nova venda</a>
		</div>
	</div>
	<p class="muted">Visualize todas as vendas com detalhes por item e exporte CSV no formato detalhado ou resumido.</p>

	<form method="GET" action="/vendas/listar" class="search-panel" style="display: grid; gap: 10px;">
		<div class="search-grid" style="grid-template-columns: repeat(6, minmax(140px, 1fr));">
			<input type="number" min="1" name="venda_id" placeholder="ID da venda" value="<?= $vendaIdAtual > 0 ? $vendaIdAtual : '' ?>">
			<input type="date" name="data_inicio" value="<?= htmlspecialchars($dataInicio, ENT_QUOTES, 'UTF-8') ?>">
			<input type="date" name="data_fim" value="<?= htmlspecialchars($dataFim, ENT_QUOTES, 'UTF-8') ?>">
			<select name="cliente_id">
				<option value="">Cliente: todos</option>
				<?php foreach ($clientes as $cliente): ?>
					<?php $id = (int) $cliente['id']; ?>
					<option value="<?= $id ?>" <?= $clienteAtual === $id ? 'selected' : '' ?>>
						<?= htmlspecialchars($cliente['nome'], ENT_QUOTES, 'UTF-8') ?>
					</option>
				<?php endforeach; ?>
			</select>
			<select name="funcionario_id">
				<option value="">Funcionario: todos</option>
				<?php foreach ($funcionarios as $funcionario): ?>
					<?php $id = (int) $funcionario['id']; ?>
					<option value="<?= $id ?>" <?= $funcionarioAtual === $id ? 'selected' : '' ?>>
						<?= htmlspecialchars($funcionario['nome'], ENT_QUOTES, 'UTF-8') ?>
					</option>
				<?php endforeach; ?>
			</select>
			<select name="ordem">
				<option value="data_desc" <?= $ordemAtual === 'data_desc' ? 'selected' : '' ?>>Mais recentes</option>
				<option value="data_asc" <?= $ordemAtual === 'data_asc' ? 'selected' : '' ?>>Mais antigas</option>
				<option value="total_desc" <?= $ordemAtual === 'total_desc' ? 'selected' : '' ?>>Maior total</option>
				<option value="total_asc" <?= $ordemAtual === 'total_asc' ? 'selected' : '' ?>>Menor total</option>
			</select>
		</div>
		<div class="pills">
			<button type="submit">Aplicar filtros</button>
			<a class="btn-subtle" href="/vendas/listar">Limpar</a>
			<a class="btn-soft" href="/vendas/exportar-csv?<?= htmlspecialchars(http_build_query($queryDetalhado), ENT_QUOTES, 'UTF-8') ?>">Exportar CSV (Detalhado)</a>
			<a class="btn-soft" href="/vendas/exportar-csv?<?= htmlspecialchars(http_build_query($queryResumo), ENT_QUOTES, 'UTF-8') ?>">Exportar CSV (Resumo)</a>
		</div>
	</form>

	<p class="helper-text"><?= count($vendas) ?> venda(s) encontrada(s).</p>

	<div class="table-wrap">
		<table>
			<thead>
				<tr>
					<th>ID</th>
					<th>Data</th>
					<th>Cliente</th>
					<th>Funcionario</th>
					<th>Itens</th>
					<th>Total</th>
					<th>Detalhes</th>
				</tr>
			</thead>
			<tbody>
				<?php if (empty($vendas)): ?>
					<tr><td colspan="7">Nenhuma venda encontrada para os filtros selecionados.</td></tr>
				<?php else: ?>
					<?php foreach ($vendas as $venda): ?>
						<?php
						$vendaId = (int) $venda['id'];
						$itensVenda = $itensPorVenda[$vendaId] ?? [];
						$clienteNome = $venda['cliente_nome'] !== null ? (string) $venda['cliente_nome'] : 'Sem cliente';
						?>
						<tr>
							<td><?= $vendaId ?></td>
							<td><?= htmlspecialchars((string) $venda['data_venda'], ENT_QUOTES, 'UTF-8') ?></td>
							<td><?= htmlspecialchars($clienteNome, ENT_QUOTES, 'UTF-8') ?></td>
							<td><?= htmlspecialchars((string) $venda['funcionario_nome'], ENT_QUOTES, 'UTF-8') ?></td>
							<td><?= (int) $venda['total_itens'] ?></td>
							<td>R$ <?= number_format((float) $venda['valor_total'], 2, ',', '.') ?></td>
							<td>
								<details>
									<summary>Ver itens (<?= count($itensVenda) ?>)</summary>
									<?php if (empty($itensVenda)): ?>
										<p class="helper-text">Venda sem itens registrados.</p>
									<?php else: ?>
										<div class="table-wrap" style="margin-top: 8px;">
											<table>
												<thead>
													<tr>
														<th>Produto</th>
														<th>Lote</th>
														<th>Validade</th>
														<th>Qtd</th>
														<th>Preco</th>
														<th>Subtotal</th>
														<th>Receita</th>
													</tr>
												</thead>
												<tbody>
													<?php foreach ($itensVenda as $item): ?>
														<tr>
															<td><?= htmlspecialchars((string) $item['produto_nome'], ENT_QUOTES, 'UTF-8') ?></td>
															<td><?= htmlspecialchars((string) $item['numero_lote'], ENT_QUOTES, 'UTF-8') ?></td>
															<td><?= htmlspecialchars((string) $item['validade'], ENT_QUOTES, 'UTF-8') ?></td>
															<td><?= (int) $item['quantidade'] ?></td>
															<td>R$ <?= number_format((float) $item['preco_unitario_momento'], 2, ',', '.') ?></td>
															<td>R$ <?= number_format((float) $item['subtotal'], 2, ',', '.') ?></td>
															<td><?= $item['receita_id'] !== null ? (int) $item['receita_id'] : '-' ?></td>
														</tr>
													<?php endforeach; ?>
												</tbody>
											</table>
										</div>
									<?php endif; ?>
								</details>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
</div>

<?php $baseUrl = (string) ($GLOBALS['BASE_URL'] ?? ''); ?>
<div class="card">
	<div class="section-head">
		<h2>Receitas</h2>
		<a class="btn-inline" href="<?= htmlspecialchars($baseUrl . '/receitas/novo', ENT_QUOTES, 'UTF-8') ?>">Nova receita</a>
	</div>
	<p class="muted">Cadastre receitas para liberar venda de produtos controlados.</p>

	<div class="table-wrap">
		<table>
			<thead>
				<tr>
					<th>ID</th>
					<th>Cliente</th>
					<th>Medico</th>
					<th>CRM</th>
					<th>Data</th>
				</tr>
			</thead>
			<tbody>
				<?php if (empty($receitas)): ?>
					<tr><td colspan="5">Nenhuma receita cadastrada.</td></tr>
				<?php else: ?>
					<?php foreach ($receitas as $receita): ?>
						<tr>
							<td><?= (int) $receita['id'] ?></td>
							<td><?= htmlspecialchars($receita['cliente_nome'], ENT_QUOTES, 'UTF-8') ?></td>
							<td><?= htmlspecialchars($receita['medico_nome'], ENT_QUOTES, 'UTF-8') ?></td>
							<td><?= htmlspecialchars($receita['crm'], ENT_QUOTES, 'UTF-8') ?></td>
							<td><?= htmlspecialchars($receita['data_receita'], ENT_QUOTES, 'UTF-8') ?></td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
</div>

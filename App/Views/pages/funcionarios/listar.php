<?php $baseUrl = (string) ($GLOBALS['BASE_URL'] ?? ''); ?>
<div class="card">
	<h2>Funcionarios</h2>
	<p><a href="<?= htmlspecialchars($baseUrl . '/funcionarios/novo', ENT_QUOTES, 'UTF-8') ?>">Novo funcionario</a></p>
	<div class="table-wrap">
		<table>
			<thead>
				<tr>
					<th>CPF</th>
					<th>Nome</th>
					<th>Cargo</th>
					<th>CRF</th>
				</tr>
			</thead>
			<tbody>
				<?php if (empty($funcionarios)): ?>
					<tr><td colspan="4">Nenhum funcionario cadastrado.</td></tr>
				<?php else: ?>
					<?php foreach ($funcionarios as $funcionario): ?>
						<tr>
							<td><?= htmlspecialchars($funcionario['cpf'], ENT_QUOTES, 'UTF-8') ?></td>
							<td><?= htmlspecialchars($funcionario['nome'], ENT_QUOTES, 'UTF-8') ?></td>
							<td><?= htmlspecialchars($funcionario['cargo'], ENT_QUOTES, 'UTF-8') ?></td>
							<td><?= htmlspecialchars((string) ($funcionario['crf'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
</div>

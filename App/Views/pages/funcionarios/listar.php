<div class="card">
	<h2>Funcionarios</h2>
	<p><a href="/funcionarios/novo">Novo funcionario</a></p>
	<table>
		<thead>
			<tr>
				<th>ID</th>
				<th>Nome</th>
				<th>Cargo</th>
				<th>CPF</th>
				<th>CRF</th>
			</tr>
		</thead>
		<tbody>
			<?php if (empty($funcionarios)): ?>
				<tr><td colspan="5">Nenhum funcionario cadastrado.</td></tr>
			<?php else: ?>
				<?php foreach ($funcionarios as $funcionario): ?>
					<tr>
						<td><?= (int) $funcionario['id'] ?></td>
						<td><?= htmlspecialchars($funcionario['nome'], ENT_QUOTES, 'UTF-8') ?></td>
						<td><?= htmlspecialchars($funcionario['cargo'], ENT_QUOTES, 'UTF-8') ?></td>
						<td><?= htmlspecialchars($funcionario['cpf'], ENT_QUOTES, 'UTF-8') ?></td>
						<td><?= htmlspecialchars((string) ($funcionario['crf'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>

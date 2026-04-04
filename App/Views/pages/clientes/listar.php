<div class="card">
	<h2>Clientes</h2>
	<p><a href="/clientes/novo">Novo cliente</a></p>
	<div class="table-wrap">
		<table>
			<thead>
				<tr>
					<th>CPF</th>
					<th>Nome</th>
					<th>Nascimento</th>
					<th>Telefone</th>
				</tr>
			</thead>
			<tbody>
				<?php if (empty($clientes)): ?>
					<tr><td colspan="4">Nenhum cliente cadastrado.</td></tr>
				<?php else: ?>
					<?php foreach ($clientes as $cliente): ?>
						<tr>
							<td><?= htmlspecialchars($cliente['cpf'], ENT_QUOTES, 'UTF-8') ?></td>
							<td><?= htmlspecialchars($cliente['nome'], ENT_QUOTES, 'UTF-8') ?></td>
							<td><?= htmlspecialchars((string) ($cliente['data_nascimento'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
							<td><?= htmlspecialchars((string) ($cliente['telefone'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
</div>

<div class="card">
	<h2>Clientes</h2>
	<p><a href="/clientes/novo">Novo cliente</a></p>
	<table>
		<thead>
			<tr>
				<th>ID</th>
				<th>Nome</th>
				<th>CPF</th>
				<th>Telefone</th>
			</tr>
		</thead>
		<tbody>
			<?php if (empty($clientes)): ?>
				<tr><td colspan="4">Nenhum cliente cadastrado.</td></tr>
			<?php else: ?>
				<?php foreach ($clientes as $cliente): ?>
					<tr>
						<td><?= (int) $cliente['id'] ?></td>
						<td><?= htmlspecialchars($cliente['nome'], ENT_QUOTES, 'UTF-8') ?></td>
						<td><?= htmlspecialchars($cliente['cpf'], ENT_QUOTES, 'UTF-8') ?></td>
						<td><?= htmlspecialchars((string) ($cliente['telefone'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>

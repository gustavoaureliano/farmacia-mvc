<div class="card">
	<div class="section-head">
		<h2>Clientes</h2>
		<?php $baseUrl = (string) ($GLOBALS['BASE_URL'] ?? ''); ?>
		<a class="btn-inline" href="<?= htmlspecialchars($baseUrl . '/clientes/novo', ENT_QUOTES, 'UTF-8') ?>">Novo cliente</a>
	</div>
	<div class="table-wrap">
		<table>
			<thead>
				<tr>
					<th>CPF</th>
					<th>Nome</th>
					<th>Nascimento</th>
					<th>Telefone</th>
					<th>Acoes</th>
				</tr>
			</thead>
			<tbody>
				<?php if (empty($clientes)): ?>
					<tr><td colspan="5">Nenhum cliente cadastrado.</td></tr>
				<?php else: ?>
					<?php foreach ($clientes as $cliente): ?>
						<?php $cpf = (string) ($cliente['cpf'] ?? ''); ?>
						<tr>
							<td><?= htmlspecialchars($cpf, ENT_QUOTES, 'UTF-8') ?></td>
							<td><?= htmlspecialchars($cliente['nome'], ENT_QUOTES, 'UTF-8') ?></td>
							<td><?= htmlspecialchars((string) ($cliente['data_nascimento'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
							<td><?= htmlspecialchars((string) ($cliente['telefone'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
							<td>
								<div class="pills" style="margin: 0;">
										<a class="btn-soft" href="<?= htmlspecialchars($baseUrl . '/clientes/editar?cpf=' . urlencode($cpf), ENT_QUOTES, 'UTF-8') ?>">Editar</a>
										<form method="POST" action="<?= htmlspecialchars($baseUrl . '/clientes/excluir', ENT_QUOTES, 'UTF-8') ?>" style="margin: 0;">
										<input type="hidden" name="cpf" value="<?= htmlspecialchars($cpf, ENT_QUOTES, 'UTF-8') ?>">
										<button type="submit" class="btn-subtle" onclick="return confirm('Excluir este cliente? Esta acao nao pode ser desfeita.');">Excluir</button>
									</form>
								</div>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
</div>

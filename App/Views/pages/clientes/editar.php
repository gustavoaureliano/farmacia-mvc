<?php
/** @var array|null $cliente */
$clienteSeguro = is_array($cliente ?? null) ? $cliente : null;
$cpf = (string) ($clienteSeguro['cpf'] ?? '');
$returnToSeguro = isset($returnTo) && is_string($returnTo) ? $returnTo : '/clientes';
?>

<div class="card">
	<div class="section-head">
		<h2>Editar cliente</h2>
		<a class="btn-inline" href="<?= htmlspecialchars($returnToSeguro, ENT_QUOTES, 'UTF-8') ?>">Voltar</a>
	</div>

	<?php if ($clienteSeguro === null || $cpf === ''): ?>
		<p class="muted">Cliente nao encontrado.</p>
	<?php else: ?>
		<form method="POST" action="/clientes/atualizar">
			<input type="hidden" name="cpf" value="<?= htmlspecialchars($cpf, ENT_QUOTES, 'UTF-8') ?>">
			<input type="hidden" name="return_to" value="<?= htmlspecialchars($returnToSeguro, ENT_QUOTES, 'UTF-8') ?>">

			<label>CPF</label>
			<input value="<?= htmlspecialchars($cpf, ENT_QUOTES, 'UTF-8') ?>" disabled>

			<label>Nome</label>
			<input name="nome" value="<?= htmlspecialchars((string) ($clienteSeguro['nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>

			<label>Nascimento</label>
			<input type="date" name="data_nascimento" value="<?= htmlspecialchars((string) ($clienteSeguro['data_nascimento'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">

			<label>Telefone</label>
			<input name="telefone" value="<?= htmlspecialchars((string) ($clienteSeguro['telefone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">

			<button type="submit">Salvar alteracoes</button>
		</form>
	<?php endif; ?>
</div>


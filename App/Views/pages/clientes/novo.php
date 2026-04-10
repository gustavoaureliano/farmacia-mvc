<div class="card">
	<h2>Novo cliente</h2>
	<?php $baseUrl = (string) ($GLOBALS['BASE_URL'] ?? ''); ?>
	<form method="POST" action="<?= htmlspecialchars($baseUrl . '/clientes/salvar', ENT_QUOTES, 'UTF-8') ?>">
		<input name="nome" placeholder="Nome" required>
		<input name="cpf" placeholder="CPF (somente numeros)" maxlength="11" required>
		<input type="date" name="data_nascimento" placeholder="Data nascimento">
		<input name="telefone" placeholder="Telefone">
		<button type="submit">Salvar</button>
	</form>
</div>

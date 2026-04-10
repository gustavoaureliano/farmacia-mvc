<?php $baseUrl = (string) ($GLOBALS['BASE_URL'] ?? ''); ?>
<div class="card">
	<h2>Novo funcionario</h2>
	<form method="POST" action="<?= htmlspecialchars($baseUrl . '/funcionarios/salvar', ENT_QUOTES, 'UTF-8') ?>">
		<input name="nome" placeholder="Nome" required>
		<select name="cargo" required>
			<option value="atendente">Atendente</option>
			<option value="farmaceutico">Farmaceutico</option>
		</select>
		<input name="cpf" placeholder="CPF (somente numeros)" maxlength="11" required>
		<input name="crf" placeholder="CRF (obrigatorio para farmaceutico)">
		<button type="submit">Salvar</button>
	</form>
</div>

<?php
$returnToSeguro = isset($returnTo) && is_string($returnTo) ? $returnTo : null;
$clienteSelecionado = isset($clienteIdSelecionado) ? (int) $clienteIdSelecionado : 0;
$produtoSelecionado = isset($produtoIdSelecionado) ? (int) $produtoIdSelecionado : 0;
$vendaOrigem = null;

if ($returnToSeguro !== null) {
	$parts = parse_url($returnToSeguro);
	if (($parts['path'] ?? '') === '/vendas/nova' && isset($parts['query'])) {
		parse_str($parts['query'], $query);
		$vendaOrigem = isset($query['venda_id']) ? (int) $query['venda_id'] : null;
	}
}
?>

<div class="card">
	<div class="section-head">
		<h2>Nova receita</h2>
		<?php if ($returnToSeguro !== null): ?>
			<a class="btn-inline" href="<?= htmlspecialchars($returnToSeguro, ENT_QUOTES, 'UTF-8') ?>">Voltar para venda</a>
		<?php else: ?>
			<a class="btn-inline" href="/receitas">Voltar para receitas</a>
		<?php endif; ?>
	</div>

	<?php if ($returnToSeguro !== null): ?>
		<p class="helper-text">Voce veio da tela de venda<?= $vendaOrigem !== null && $vendaOrigem > 0 ? ' #' . $vendaOrigem : '' ?>. Ao salvar, o sistema retorna automaticamente para continuar o atendimento.</p>
	<?php endif; ?>

	<form method="POST" action="/receitas/salvar">
		<?php if ($returnToSeguro !== null): ?>
			<input type="hidden" name="return_to" value="<?= htmlspecialchars($returnToSeguro, ENT_QUOTES, 'UTF-8') ?>">
		<?php endif; ?>

		<label>Cliente</label>
		<select name="cliente_id" required>
			<option value="">Selecione</option>
			<?php foreach ($clientes as $cliente): ?>
				<?php $id = (int) $cliente['id']; ?>
				<option value="<?= $id ?>" <?= $clienteSelecionado === $id ? 'selected' : '' ?>>
					<?= htmlspecialchars($cliente['nome'], ENT_QUOTES, 'UTF-8') ?>
				</option>
			<?php endforeach; ?>
		</select>

		<label>Medico</label>
		<input name="medico_nome" placeholder="Nome do medico" required>

		<label>CRM</label>
		<input name="crm" placeholder="CRM" required>

		<label>Data da receita</label>
		<input type="date" name="data_receita" value="<?= htmlspecialchars(date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>" required>

		<label>Produto autorizado</label>
		<select name="produto_id" required>
			<option value="">Selecione</option>
			<?php foreach ($produtos as $produto): ?>
				<?php $id = (int) $produto['id']; ?>
				<option value="<?= $id ?>" <?= $produtoSelecionado === $id ? 'selected' : '' ?>>
					<?= htmlspecialchars($produto['nome'], ENT_QUOTES, 'UTF-8') ?>
				</option>
			<?php endforeach; ?>
		</select>

		<label>Posologia (opcional)</label>
		<input name="posologia" placeholder="Ex.: 1 comprimido a cada 8h">

		<label>Observacoes (opcional)</label>
		<textarea name="observacoes" rows="3" placeholder="Observacoes adicionais"></textarea>

		<button type="submit">Salvar receita</button>
	</form>
</div>

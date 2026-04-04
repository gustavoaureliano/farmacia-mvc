<?php
$returnToSeguro = isset($returnTo) && is_string($returnTo) ? $returnTo : null;
$produtoSelecionado = isset($produtoIdSelecionado) ? (int) $produtoIdSelecionado : 0;
$novoProdutoQuery = http_build_query([
	'return_to' => '/lotes/novo',
]);
?>

<div class="card">
	<div class="section-head">
		<h2>Novo lote</h2>
		<?php if ($returnToSeguro !== null): ?>
			<a class="btn-inline" href="<?= htmlspecialchars($returnToSeguro, ENT_QUOTES, 'UTF-8') ?>">Voltar</a>
		<?php else: ?>
			<a class="btn-inline" href="/lotes">Voltar para lotes</a>
		<?php endif; ?>
	</div>
	<p class="helper-text">Se o produto ainda nao existir, cadastre agora e retorne para continuar o lote.</p>
	<div class="pills">
		<a class="btn-soft" href="/produtos/novo?<?= htmlspecialchars($novoProdutoQuery, ENT_QUOTES, 'UTF-8') ?>">Novo produto</a>
	</div>

	<form method="POST" action="/lotes/salvar">
		<?php if ($returnToSeguro !== null): ?>
			<input type="hidden" name="return_to" value="<?= htmlspecialchars($returnToSeguro, ENT_QUOTES, 'UTF-8') ?>">
		<?php endif; ?>

		<label>Produto</label>
		<select name="produto_id" required>
			<option value="">Selecione</option>
			<?php foreach ($produtos as $produto): ?>
				<?php $id = (int) $produto['id']; ?>
				<option value="<?= $id ?>" <?= $produtoSelecionado === $id ? 'selected' : '' ?>>
					<?= htmlspecialchars($produto['nome'], ENT_QUOTES, 'UTF-8') ?>
				</option>
			<?php endforeach; ?>
		</select>

		<label>Numero do lote</label>
		<input name="numero_lote" placeholder="Ex.: DIP-003" required>

		<label>Validade</label>
		<input type="date" name="validade" required>

		<label>Quantidade disponivel</label>
		<input type="number" name="quantidade_disponivel" min="1" required>

		<label>Localizacao (opcional)</label>
		<input name="localizacao" placeholder="Ex.: Prateleira C2">

		<button type="submit">Salvar lote</button>
	</form>
</div>

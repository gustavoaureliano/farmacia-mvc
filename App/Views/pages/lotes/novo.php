<?php
$baseUrl = (string) ($GLOBALS['BASE_URL'] ?? '');
$returnToSeguro = isset($returnTo) && is_string($returnTo) ? $returnTo : null;
$returnToHref = $returnToSeguro !== null && str_starts_with($returnToSeguro, '/')
	? $baseUrl . $returnToSeguro
	: $returnToSeguro;
$produtoSelecionado = isset($produtoCodSelecionado) && is_string($produtoCodSelecionado) ? $produtoCodSelecionado : '';
$novoProdutoQuery = http_build_query([
	'return_to' => '/estoque/novo',
]);
?>

<div class="card">
	<div class="section-head">
		<h2>Nova entrada de estoque</h2>
		<?php if ($returnToSeguro !== null): ?>
			<a class="btn-inline" href="<?= htmlspecialchars((string) $returnToHref, ENT_QUOTES, 'UTF-8') ?>">Voltar</a>
		<?php else: ?>
			<a class="btn-inline" href="<?= htmlspecialchars($baseUrl . '/estoque', ENT_QUOTES, 'UTF-8') ?>">Voltar para estoque</a>
		<?php endif; ?>
	</div>
	<p class="helper-text">Se o produto ainda nao existir, cadastre agora e continue o lancamento no estoque.</p>
	<div class="pills">
		<a class="btn-soft" href="<?= htmlspecialchars($baseUrl . '/produtos/novo?' . $novoProdutoQuery, ENT_QUOTES, 'UTF-8') ?>">Novo produto</a>
	</div>

	<form method="POST" action="<?= htmlspecialchars($baseUrl . '/estoque/salvar', ENT_QUOTES, 'UTF-8') ?>">
		<?php if ($returnToSeguro !== null): ?>
			<input type="hidden" name="return_to" value="<?= htmlspecialchars($returnToSeguro, ENT_QUOTES, 'UTF-8') ?>">
		<?php endif; ?>

		<label>Produto</label>
		<select name="cod_barras" required>
			<option value="">Selecione</option>
			<?php foreach ($produtos as $produto): ?>
				<?php $codigo = (string) $produto['codigo_barras']; ?>
				<option value="<?= htmlspecialchars($codigo, ENT_QUOTES, 'UTF-8') ?>" <?= $produtoSelecionado === $codigo ? 'selected' : '' ?>>
					<?= htmlspecialchars($produto['nome'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($codigo, ENT_QUOTES, 'UTF-8') ?>)
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

		<button type="submit">Salvar entrada</button>
	</form>
</div>

<?php $returnToSeguro = isset($returnTo) && is_string($returnTo) ? $returnTo : null; ?>

<div class="card">
	<div class="section-head">
		<h2>Novo produto</h2>
		<?php if ($returnToSeguro !== null): ?>
			<a class="btn-inline" href="<?= htmlspecialchars($returnToSeguro, ENT_QUOTES, 'UTF-8') ?>">Voltar</a>
		<?php else: ?>
			<a class="btn-inline" href="/produtos">Voltar para produtos</a>
		<?php endif; ?>
	</div>
	<p class="helper-text">Opcionalmente, voce pode cadastrar o lote inicial junto com o produto.</p>

	<form method="POST" action="/produtos/salvar">
		<?php if ($returnToSeguro !== null): ?>
			<input type="hidden" name="return_to" value="<?= htmlspecialchars($returnToSeguro, ENT_QUOTES, 'UTF-8') ?>">
		<?php endif; ?>

		<label>Nome</label>
		<input name="nome" placeholder="Nome" required>
		<label>Principio ativo (opcional)</label>
		<input name="principio_ativo" placeholder="Principio ativo">
		<label>Marca/Laboratorio (opcional)</label>
		<input name="marca_laboratorio" placeholder="Marca ou laboratorio">
		<label>Tipo</label>
		<select name="tipo" required>
			<option value="generico">Generico</option>
			<option value="similar">Similar</option>
			<option value="referencia">Referencia</option>
		</select>
		<label><input type="checkbox" name="exige_receita" value="1"> Exige receita</label>
		<label>Preco</label>
		<input type="number" step="0.01" min="0.01" name="preco_atual" placeholder="Preco" required>
		<label>Codigo de barras</label>
		<input name="codigo_barras" placeholder="Codigo de barras" required>

		<div class="search-panel">
			<h3 style="margin: 0 0 8px;">Lote inicial (opcional)</h3>
			<label>Numero do lote</label>
			<input name="lote_numero" placeholder="Ex.: DIP-010">

			<label>Validade</label>
			<input type="date" name="lote_validade">

			<label>Quantidade inicial</label>
			<input type="number" min="1" name="lote_quantidade" placeholder="Ex.: 50">

			<label>Localizacao (opcional)</label>
			<input name="lote_localizacao" placeholder="Ex.: Gaveta B2">
		</div>

		<button type="submit">Salvar</button>
	</form>
</div>

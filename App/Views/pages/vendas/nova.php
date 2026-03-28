<div class="card">
	<h2>Nova venda</h2>
	<?php if (empty($venda)): ?>
		<form method="POST" action="/vendas/criar">
			<label>Cliente (opcional)</label>
			<select name="cliente_id">
				<option value="">Sem cliente</option>
				<?php foreach ($clientes as $cliente): ?>
					<option value="<?= (int) $cliente['id'] ?>"><?= htmlspecialchars($cliente['nome'], ENT_QUOTES, 'UTF-8') ?></option>
				<?php endforeach; ?>
			</select>

			<label>Funcionario</label>
			<select name="funcionario_id" required>
				<option value="">Selecione</option>
				<?php foreach ($funcionarios as $funcionario): ?>
					<option value="<?= (int) $funcionario['id'] ?>"><?= htmlspecialchars($funcionario['nome'], ENT_QUOTES, 'UTF-8') ?></option>
				<?php endforeach; ?>
			</select>

			<button type="submit">Iniciar venda</button>
		</form>
	<?php else: ?>
		<p><strong>Venda #<?= (int) $venda['id'] ?></strong> | Total atual: R$ <?= number_format((float) $venda['valor_total'], 2, ',', '.') ?></p>
		<form method="POST" action="/vendas/adicionar-item">
			<input type="hidden" name="venda_id" value="<?= (int) $venda['id'] ?>">

			<label>Produto</label>
			<select name="produto_id" required>
				<option value="">Selecione</option>
				<?php foreach ($produtos as $produto): ?>
					<option value="<?= (int) $produto['id'] ?>">
						<?= htmlspecialchars($produto['nome'], ENT_QUOTES, 'UTF-8') ?>
						(R$ <?= number_format((float) $produto['preco_atual'], 2, ',', '.') ?>)
					</option>
				<?php endforeach; ?>
			</select>

			<label>Quantidade</label>
			<input type="number" name="quantidade" min="1" required>

			<label>Receita ID (obrigatorio apenas para controlado)</label>
			<input type="number" name="receita_id" min="1" placeholder="Opcional">

			<button type="submit">Adicionar item (FEFO)</button>
		</form>

		<h3>Itens da venda</h3>
		<table>
			<thead>
				<tr>
					<th>Produto</th>
					<th>Lote</th>
					<th>Validade</th>
					<th>Qtd</th>
					<th>Preco</th>
					<th>Subtotal</th>
				</tr>
			</thead>
			<tbody>
				<?php if (empty($itens)): ?>
					<tr><td colspan="6">Nenhum item adicionado.</td></tr>
				<?php else: ?>
					<?php foreach ($itens as $item): ?>
						<tr>
							<td><?= htmlspecialchars($item['produto_nome'], ENT_QUOTES, 'UTF-8') ?></td>
							<td><?= htmlspecialchars($item['numero_lote'], ENT_QUOTES, 'UTF-8') ?></td>
							<td><?= htmlspecialchars($item['validade'], ENT_QUOTES, 'UTF-8') ?></td>
							<td><?= (int) $item['quantidade'] ?></td>
							<td>R$ <?= number_format((float) $item['preco_unitario_momento'], 2, ',', '.') ?></td>
							<td>R$ <?= number_format((float) $item['subtotal'], 2, ',', '.') ?></td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>

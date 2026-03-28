<div class="card">
	<h2>Produtos</h2>
	<p><a href="/produtos/novo">Novo produto</a></p>
	<table>
		<thead>
			<tr>
				<th>ID</th>
				<th>Nome</th>
				<th>Tipo</th>
				<th>Receita</th>
				<th>Preco</th>
				<th>Codigo barras</th>
			</tr>
		</thead>
		<tbody>
			<?php if (empty($produtos)): ?>
				<tr><td colspan="6">Nenhum produto cadastrado.</td></tr>
			<?php else: ?>
				<?php foreach ($produtos as $produto): ?>
					<tr>
						<td><?= (int) $produto['id'] ?></td>
						<td><?= htmlspecialchars($produto['nome'], ENT_QUOTES, 'UTF-8') ?></td>
						<td><?= htmlspecialchars($produto['tipo'], ENT_QUOTES, 'UTF-8') ?></td>
						<td><?= ((int) $produto['exige_receita'] === 1) ? 'Sim' : 'Nao' ?></td>
						<td>R$ <?= number_format((float) $produto['preco_atual'], 2, ',', '.') ?></td>
						<td><?= htmlspecialchars($produto['codigo_barras'], ENT_QUOTES, 'UTF-8') ?></td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>

<div class="card">
	<div class="section-head">
		<h2>Lotes e estoque</h2>
		<a class="btn-inline" href="/lotes/novo">Novo lote</a>
	</div>
	<p class="muted">Resumo por produto com alerta de validade (janela de atencao: ate <?= (int) ($diasRiscoAtencao ?? 90) ?> dias).</p>

	<?php if (!empty($resumoEstoque)): ?>
		<div class="search-panel">
			<div class="search-grid" style="grid-template-columns: minmax(220px, 1fr) minmax(180px, 1fr);">
				<select id="resumo-risco-filtro">
					<option value="todos">Mostrar todos</option>
					<option value="critico">Somente criticos</option>
					<option value="atencao">Somente atencao</option>
					<option value="ok">Somente OK</option>
					<option value="sem_estoque">Somente sem estoque</option>
				</select>
				<p class="helper-text" id="resumo-risco-status" style="margin: 0; align-self: center;">Mostrando todos os produtos.</p>
			</div>
		</div>
	<?php endif; ?>

	<div class="summary-grid" id="resumo-estoque-grid">
		<?php if (empty($resumoEstoque)): ?>
			<div class="summary-card">
				<h4>Sem produtos ativos</h4>
				<p>Cadastre produtos para iniciar o controle de estoque.</p>
			</div>
		<?php else: ?>
			<?php foreach ($resumoEstoque as $resumo): ?>
				<?php
				$risco = (string) ($resumo['risco_validade'] ?? 'ok');
				$estoque = (int) ($resumo['estoque_valido'] ?? 0);
				$dias = $resumo['dias_para_vencer'] !== null ? (int) $resumo['dias_para_vencer'] : null;
				$classe = 'pill-ok';
				$label = 'OK';

				if ($risco === 'critico') {
					$classe = 'pill-critico';
					$label = 'Critico';
				} elseif ($risco === 'atencao') {
					$classe = 'pill-atencao';
					$label = 'Atencao';
				} elseif ($risco === 'sem_estoque') {
					$classe = 'pill-sem-estoque';
					$label = 'Sem estoque';
				}
				?>
				<div class="summary-card" data-risco="<?= htmlspecialchars($risco, ENT_QUOTES, 'UTF-8') ?>">
					<h4><?= htmlspecialchars($resumo['produto_nome'], ENT_QUOTES, 'UTF-8') ?></h4>
					<div class="pills" style="margin: 6px 0;">
						<span class="pill <?= $classe ?>"><?= $label ?></span>
						<span class="pill">Estoque: <?= $estoque ?></span>
						<span class="pill">Lotes: <?= (int) ($resumo['lotes_validos'] ?? 0) ?></span>
					</div>
					<p>
						<?php if ($dias === null): ?>
							Sem lote valido para venda.
						<?php else: ?>
							Proximo vencimento em <?= $dias ?> dia(s): <?= htmlspecialchars((string) $resumo['proxima_validade'], ENT_QUOTES, 'UTF-8') ?>.
						<?php endif; ?>
					</p>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>

	<?php if (!empty($resumoEstoque)): ?>
		<script>
		window.addEventListener('DOMContentLoaded', () => {
			const filtro = document.getElementById('resumo-risco-filtro');
			const status = document.getElementById('resumo-risco-status');
			const cards = Array.from(document.querySelectorAll('#resumo-estoque-grid .summary-card[data-risco]'));
			const linhasTabela = Array.from(document.querySelectorAll('.table-wrap tbody tr[data-risco]'));

			if (!filtro || !status || cards.length === 0) {
				return;
			}

			const labels = {
				todos: 'todos os produtos',
				critico: 'produtos em risco critico',
				atencao: 'produtos em atencao',
				ok: 'produtos em status OK',
				sem_estoque: 'produtos sem estoque valido',
			};

			const aplicarFiltro = () => {
				const tipo = filtro.value || 'todos';
				let visiveis = 0;
				let linhasVisiveis = 0;

				cards.forEach((card) => {
					const risco = card.getAttribute('data-risco') || '';
					const mostrar = tipo === 'todos' || risco === tipo;
					card.style.display = mostrar ? '' : 'none';
					if (mostrar) {
						visiveis += 1;
					}
				});

				linhasTabela.forEach((linha) => {
					const risco = linha.getAttribute('data-risco') || '';
					const vencido = linha.getAttribute('data-vencido') === '1';
					const mostrar = tipo === 'todos' || risco === tipo;
					linha.style.display = mostrar ? '' : 'none';
					linha.style.opacity = tipo === 'todos' && vencido ? '0.52' : '1';
					if (mostrar) {
						linhasVisiveis += 1;
					}
				});

				status.textContent = `${visiveis} produto(s) e ${linhasVisiveis} lote(s) exibidos em ${labels[tipo] || 'filtro selecionado'}.`;
			};

			filtro.addEventListener('change', aplicarFiltro);
			aplicarFiltro();
		});
		</script>
	<?php endif; ?>

	<div class="table-wrap">
		<table>
			<thead>
				<tr>
					<th>ID</th>
					<th>Produto</th>
					<th>Lote</th>
					<th>Risco</th>
					<th>Validade</th>
					<th>Qtd disponivel</th>
					<th>Localizacao</th>
				</tr>
			</thead>
			<tbody>
				<?php if (empty($lotes)): ?>
					<tr><td colspan="7">Nenhum lote cadastrado.</td></tr>
				<?php else: ?>
					<?php foreach ($lotes as $lote): ?>
						<?php
						$qtd = (int) $lote['quantidade_disponivel'];
						$validadeRaw = (string) $lote['validade'];
						$hoje = new DateTimeImmutable('today');
						$validade = new DateTimeImmutable($validadeRaw);
						$dias = (int) $hoje->diff($validade)->format('%r%a');
						$riscoTabela = 'ok';
						$labelTabela = 'OK';
						$classeTabela = 'pill-ok';

						if ($qtd <= 0 || $dias < 0) {
							$riscoTabela = 'sem_estoque';
							$labelTabela = 'Sem estoque';
							$classeTabela = 'pill-sem-estoque';
						} elseif ($dias <= 30) {
							$riscoTabela = 'critico';
							$labelTabela = 'Critico';
							$classeTabela = 'pill-critico';
						} elseif ($dias <= (int) ($diasRiscoAtencao ?? 90)) {
							$riscoTabela = 'atencao';
							$labelTabela = 'Atencao';
							$classeTabela = 'pill-atencao';
						}
						?>
						<tr data-risco="<?= htmlspecialchars($riscoTabela, ENT_QUOTES, 'UTF-8') ?>" data-vencido="<?= $dias < 0 ? '1' : '0' ?>">
							<td><?= (int) $lote['id'] ?></td>
							<td><?= htmlspecialchars($lote['produto_nome'], ENT_QUOTES, 'UTF-8') ?></td>
							<td><?= htmlspecialchars($lote['numero_lote'], ENT_QUOTES, 'UTF-8') ?></td>
							<td><span class="pill <?= $classeTabela ?>"><?= $labelTabela ?></span></td>
							<td><?= htmlspecialchars($lote['validade'], ENT_QUOTES, 'UTF-8') ?></td>
							<td><?= (int) $lote['quantidade_disponivel'] ?></td>
							<td><?= htmlspecialchars((string) ($lote['localizacao'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
</div>

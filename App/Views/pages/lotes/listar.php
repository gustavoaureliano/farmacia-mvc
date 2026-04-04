<div class="card">
	<div class="section-head">
		<h2>Estoque</h2>
		<a class="btn-inline" href="/estoque/novo">Nova entrada</a>
	</div>
	<p class="muted">Resumo de estoque com alertas de validade (ate <?= (int) ($diasRiscoAtencao ?? 90) ?> dias).</p>

	<?php if (!empty($resumoEstoque)): ?>
		<div class="search-panel">
			<div class="search-grid" style="grid-template-columns: minmax(220px, 1fr) minmax(180px, 1fr);">
				<select id="resumo-risco-filtro">
					<option value="atencao_critico" selected>Critico + Atencao</option>
					<option value="todos">Mostrar todos</option>
					<option value="critico">Somente criticos</option>
					<option value="atencao">Somente atencao</option>
					<option value="ok">Somente OK</option>
					<option value="sem_estoque">Somente sem estoque</option>
				</select>
				<p class="helper-text" id="resumo-risco-status" style="margin: 0; align-self: center;" aria-live="polite">Exibindo produtos com maior prioridade.</p>
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
						<span class="pill">Entradas: <?= (int) ($resumo['lotes_validos'] ?? 0) ?></span>
					</div>
					<p>
						<?php if ($dias === null): ?>
							Sem entrada valida para venda.
						<?php else: ?>
							Proximo vencimento em <?= $dias ?> dia(s): <?= htmlspecialchars((string) $resumo['proxima_validade'], ENT_QUOTES, 'UTF-8') ?>.
						<?php endif; ?>
					</p>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>

	<?php if (!empty($lotes)): ?>
		<div class="search-panel">
			<div class="search-grid" style="grid-template-columns: minmax(190px, 1fr) minmax(220px, 1fr) minmax(240px, 1fr);">
				<select id="lista-risco-filtro">
					<option value="todos" selected>Todos os riscos</option>
					<option value="critico">Somente criticos</option>
					<option value="atencao">Somente atencao</option>
					<option value="ok">Somente OK</option>
					<option value="sem_estoque">Somente sem estoque</option>
				</select>
				<select id="lista-ordem">
					<option value="risco" selected>Ordenar por risco</option>
					<option value="validade_asc">Validade mais proxima</option>
					<option value="validade_desc">Validade mais distante</option>
					<option value="qtd_desc">Maior quantidade</option>
					<option value="produto_asc">Produto A-Z</option>
					<option value="lote_asc">Lote A-Z</option>
				</select>
				<p class="helper-text" id="lista-lotes-status" style="margin: 0; align-self: center;" aria-live="polite">Mostrando todo o estoque.</p>
			</div>
		</div>
	<?php endif; ?>

	<script>
	window.addEventListener('DOMContentLoaded', () => {
		const STORAGE_RESUMO = 'lotesResumoFiltro';
		const STORAGE_LISTA_FILTRO = 'lotesListaFiltro';
		const STORAGE_LISTA_ORDEM = 'lotesListaOrdem';

		const resumoFiltro = document.getElementById('resumo-risco-filtro');
		const resumoStatus = document.getElementById('resumo-risco-status');
		const cards = Array.from(document.querySelectorAll('#resumo-estoque-grid .summary-card[data-risco]'));

		const listaFiltro = document.getElementById('lista-risco-filtro');
		const listaOrdem = document.getElementById('lista-ordem');
		const listaStatus = document.getElementById('lista-lotes-status');
		const tabelaBody = document.querySelector('.table-wrap tbody');
		const linhasTabela = tabelaBody ? Array.from(tabelaBody.querySelectorAll('tr[data-risco]')) : [];

		const labelsResumo = {
			todos: 'todos os produtos',
			atencao_critico: 'produtos criticos e em atencao',
			critico: 'produtos criticos',
			atencao: 'produtos em atencao',
			ok: 'produtos com estoque regular',
			sem_estoque: 'produtos sem estoque disponivel',
		};

		const labelsLista = {
			todos: 'todo o estoque',
			critico: 'itens criticos',
			atencao: 'itens em atencao',
			ok: 'itens com estoque regular',
			sem_estoque: 'itens sem estoque disponivel',
		};

		const labelsOrdem = {
			risco: 'prioridade',
			validade_asc: 'validade mais proxima',
			validade_desc: 'validade mais distante',
			qtd_desc: 'maior quantidade',
			produto_asc: 'produto A-Z',
			lote_asc: 'lote A-Z',
		};

		const rankRisco = { critico: 0, atencao: 1, ok: 2, sem_estoque: 3 };

		const matchFiltro = (tipo, risco) => {
			if (tipo === 'todos') {
				return true;
			}

			if (tipo === 'atencao_critico') {
				return risco === 'critico' || risco === 'atencao';
			}

			return risco === tipo;
		};

		const aplicarResumo = () => {
			if (!resumoFiltro || !resumoStatus || cards.length === 0) {
				return;
			}

			const tipo = resumoFiltro.value || 'atencao_critico';
			let visiveis = 0;

			cards.forEach((card) => {
				const risco = card.getAttribute('data-risco') || '';
				const mostrar = matchFiltro(tipo, risco);
				card.style.display = mostrar ? '' : 'none';
				if (mostrar) {
					visiveis += 1;
				}
			});

			resumoStatus.textContent = `${visiveis} produto(s) exibido(s) em ${labelsResumo[tipo] || 'selecao atual'}.`;
			localStorage.setItem(STORAGE_RESUMO, tipo);
		};

		const ordenarVisiveis = (visiveis, ordem) => {
			const toNum = (value) => Number(value || 0);
			const toStr = (value) => String(value || '');

			const sorted = [...visiveis];
			sorted.sort((a, b) => {
				if (ordem === 'validade_asc') {
					return toNum(a.dataset.dias) - toNum(b.dataset.dias);
				}

				if (ordem === 'validade_desc') {
					return toNum(b.dataset.dias) - toNum(a.dataset.dias);
				}

				if (ordem === 'qtd_desc') {
					return toNum(b.dataset.qtd) - toNum(a.dataset.qtd);
				}

				if (ordem === 'produto_asc') {
					return toStr(a.dataset.produto).localeCompare(toStr(b.dataset.produto), 'pt-BR');
				}

				if (ordem === 'lote_asc') {
					return toStr(a.dataset.lote).localeCompare(toStr(b.dataset.lote), 'pt-BR');
				}

				const riscoA = rankRisco[toStr(a.dataset.risco)] ?? 99;
				const riscoB = rankRisco[toStr(b.dataset.risco)] ?? 99;
				if (riscoA !== riscoB) {
					return riscoA - riscoB;
				}

				return toNum(a.dataset.dias) - toNum(b.dataset.dias);
			});

			return sorted;
		};

		const aplicarLista = () => {
			if (!listaFiltro || !listaOrdem || !listaStatus || !tabelaBody || linhasTabela.length === 0) {
				return;
			}

			const tipo = listaFiltro.value || 'todos';
			const ordem = listaOrdem.value || 'risco';

			const visiveis = linhasTabela.filter((linha) => {
				const risco = linha.dataset.risco || '';
				return matchFiltro(tipo, risco);
			});

			const ocultas = linhasTabela.filter((linha) => !visiveis.includes(linha));
			const ordenadas = ordenarVisiveis(visiveis, ordem);

			ordenadas.forEach((linha) => {
				const vencido = linha.dataset.vencido === '1';
				linha.style.display = '';
				linha.style.opacity = tipo === 'todos' && vencido ? '0.52' : '1';
			});

			ocultas.forEach((linha) => {
				linha.style.display = 'none';
				linha.style.opacity = '1';
			});

			tabelaBody.replaceChildren(...ordenadas, ...ocultas);

			listaStatus.textContent = `${ordenadas.length} item(ns) exibido(s) em ${labelsLista[tipo] || 'selecao atual'}, ordenados por ${labelsOrdem[ordem] || 'ordem atual'}.`;

			localStorage.setItem(STORAGE_LISTA_FILTRO, tipo);
			localStorage.setItem(STORAGE_LISTA_ORDEM, ordem);
		};

		if (resumoFiltro) {
			const salvoResumo = localStorage.getItem(STORAGE_RESUMO);
			if (salvoResumo && Array.from(resumoFiltro.options).some((opt) => opt.value === salvoResumo)) {
				resumoFiltro.value = salvoResumo;
			}
			resumoFiltro.addEventListener('change', aplicarResumo);
			aplicarResumo();
		}

		if (listaFiltro && listaOrdem) {
			const salvoListaFiltro = localStorage.getItem(STORAGE_LISTA_FILTRO);
			if (salvoListaFiltro && Array.from(listaFiltro.options).some((opt) => opt.value === salvoListaFiltro)) {
				listaFiltro.value = salvoListaFiltro;
			}

			const salvoListaOrdem = localStorage.getItem(STORAGE_LISTA_ORDEM);
			if (salvoListaOrdem && Array.from(listaOrdem.options).some((opt) => opt.value === salvoListaOrdem)) {
				listaOrdem.value = salvoListaOrdem;
			}

			listaFiltro.addEventListener('change', aplicarLista);
			listaOrdem.addEventListener('change', aplicarLista);
			aplicarLista();
		}
	});
	</script>

	<div class="table-wrap">
		<table>
			<thead>
				<tr>
					<th>Cod barras</th>
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
						<tr
							data-risco="<?= htmlspecialchars($riscoTabela, ENT_QUOTES, 'UTF-8') ?>"
							data-vencido="<?= $dias < 0 ? '1' : '0' ?>"
							data-dias="<?= $dias ?>"
							data-qtd="<?= $qtd ?>"
							data-produto="<?= htmlspecialchars(strtolower((string) $lote['produto_nome']), ENT_QUOTES, 'UTF-8') ?>"
							data-lote="<?= htmlspecialchars(strtolower((string) $lote['numero_lote']), ENT_QUOTES, 'UTF-8') ?>"
						>
							<td><?= htmlspecialchars((string) $lote['cod_barras'], ENT_QUOTES, 'UTF-8') ?></td>
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

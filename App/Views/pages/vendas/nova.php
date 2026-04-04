<?php if (!empty($finalizadaId)): ?>
	<div class="card">
		<h3>Venda finalizada</h3>
		<p class="muted">A venda #<?= (int) $finalizadaId ?> foi finalizada e a tela foi limpa para um novo atendimento.</p>
		<div class="pills">
			<a class="btn-soft" href="/vendas/listar?venda_id=<?= (int) $finalizadaId ?>">Ver ultima venda finalizada</a>
		</div>
	</div>
<?php endif; ?>

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
		<p><strong>Venda #<?= (int) $venda['id'] ?></strong> | Total atual: R$ <?= number_format((float) $venda['valor_total'], 2, ',', '.') ?> | Itens: <?= count($itens) ?></p>
		<p class="helper-text">Pesquise produtos, ajuste as quantidades e finalize a venda ao concluir o atendimento.</p>

		<form method="POST" action="/vendas/adicionar-item" id="form-adicionar-item">
			<input type="hidden" name="venda_id" value="<?= (int) $venda['id'] ?>">
			<input type="hidden" name="produto_id" id="venda-produto-id" required>
			<input type="hidden" id="venda-cliente-id" value="<?= isset($venda['cliente_id']) && $venda['cliente_id'] !== null ? (int) $venda['cliente_id'] : 0 ?>">

			<div class="search-panel">
				<label for="venda-produto-search">Produto</label>
				<input id="venda-produto-search" placeholder="Digite nome, codigo de barras, principio ativo ou laboratorio" autocomplete="off">
				<p class="helper-text" id="venda-search-status">Digite ao menos 2 caracteres para consultar o banco.</p>
				<ul id="venda-search-results" class="search-result-list"></ul>
				<div id="venda-produto-selecionado" class="pills" style="display: none;"></div>
			</div>

			<label>Quantidade</label>
			<input type="number" name="quantidade" min="1" step="1" value="1" required>
			<p class="helper-text" id="venda-qtd-helper">Selecione um produto para visualizar estoque disponivel.</p>
			<p class="helper-text" id="venda-subtotal-prev">Subtotal previsto: R$ 0,00</p>

			<div id="venda-receita-wrap" style="display: none;">
				<label for="venda-receita-id">Receita valida para este cliente/produto</label>
				<select name="receita_id" id="venda-receita-id">
					<option value="">Selecione a receita</option>
				</select>
				<div class="pills">
					<a class="btn-soft" id="venda-link-nova-receita" href="#">Nova receita</a>
				</div>
				<p class="helper-text" id="venda-receita-helper">Para medicamentos com receita, selecione uma receita valida.</p>
			</div>

			<button type="submit">Adicionar item</button>
		</form>

		<h3>Itens da venda</h3>
		<form method="POST" action="/vendas/finalizar" style="margin-top: 8px;">
			<input type="hidden" name="venda_id" value="<?= (int) $venda['id'] ?>">
			<div class="table-wrap">
				<table>
					<thead>
						<tr>
							<th>ID</th>
							<th>Produto</th>
							<th>Lote</th>
							<th>Validade</th>
							<th>Qtd</th>
							<th>Preco</th>
							<th>Subtotal</th>
							<th>Acoes</th>
						</tr>
					</thead>
					<tbody>
						<?php if (empty($itens)): ?>
							<tr><td colspan="8">Nenhum item adicionado.</td></tr>
						<?php else: ?>
							<?php foreach ($itens as $item): ?>
								<tr>
									<td><?= (int) $item['id'] ?></td>
									<td><?= htmlspecialchars($item['produto_nome'], ENT_QUOTES, 'UTF-8') ?></td>
									<td><?= htmlspecialchars($item['numero_lote'], ENT_QUOTES, 'UTF-8') ?></td>
									<td><?= htmlspecialchars($item['validade'], ENT_QUOTES, 'UTF-8') ?></td>
									<td>
										<input type="number" name="quantidades[<?= (int) $item['id'] ?>]" min="1" step="1" value="<?= (int) $item['quantidade'] ?>" required style="max-width: 90px;">
									</td>
									<td>R$ <?= number_format((float) $item['preco_unitario_momento'], 2, ',', '.') ?></td>
									<td>R$ <?= number_format((float) $item['subtotal'], 2, ',', '.') ?></td>
									<td>
										<button
											type="submit"
											name="item_id"
											value="<?= (int) $item['id'] ?>"
											formaction="/vendas/remover-item"
											formmethod="post"
											onclick="return confirm('Remover este item da venda?');"
											class="btn-subtle"
										>Remover</button>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
			</div>

			<button type="submit" style="margin-top: 12px;" <?= empty($itens) ? 'disabled' : '' ?>>Finalizar venda</button>
		</form>
	<?php endif; ?>
</div>

<?php if (!empty($venda)): ?>
	<?php
	$produtosBuscaInicialVenda = [];
	foreach ($produtos as $produto) {
		$produtosBuscaInicialVenda[] = [
			'id' => (int) $produto['id'],
			'nome' => (string) $produto['nome'],
			'tipo' => (string) $produto['tipo'],
			'exige_receita' => (int) $produto['exige_receita'],
			'estoque_disponivel' => (int) ($produto['estoque_disponivel'] ?? 0),
			'preco_atual' => (float) $produto['preco_atual'],
			'codigo_barras' => (string) $produto['codigo_barras'],
			'principio_ativo' => (string) ($produto['principio_ativo'] ?? ''),
			'marca_laboratorio' => (string) ($produto['marca_laboratorio'] ?? ''),
		];
	}
	?>
	<script>
	(() => {
		const endpoint = '/produtos/buscar';
		const receitasEndpoint = '/vendas/receitas-validas';
		const vendaId = <?= (int) $venda['id'] ?>;
		const returnTo = `/vendas/nova?venda_id=${vendaId}`;

		const form = document.getElementById('form-adicionar-item');
		const inputSearch = document.getElementById('venda-produto-search');
		const inputProdutoId = document.getElementById('venda-produto-id');
		const inputQtd = document.querySelector('input[name="quantidade"]');
		const inputClienteId = document.getElementById('venda-cliente-id');
		const list = document.getElementById('venda-search-results');
		const status = document.getElementById('venda-search-status');
		const selectedWrap = document.getElementById('venda-produto-selecionado');
		const qtdHelper = document.getElementById('venda-qtd-helper');
		const subtotalPrev = document.getElementById('venda-subtotal-prev');
		const receitaWrap = document.getElementById('venda-receita-wrap');
		const receitaSelect = document.getElementById('venda-receita-id');
		const receitaHelper = document.getElementById('venda-receita-helper');
		const receitaLink = document.getElementById('venda-link-nova-receita');

		if (!form || !inputSearch || !inputProdutoId || !inputQtd || !list || !status || !selectedWrap || !qtdHelper || !subtotalPrev || !receitaWrap || !receitaSelect || !receitaHelper || !receitaLink) {
			return;
		}

		const clienteId = Number(inputClienteId ? inputClienteId.value : 0);
		const initialItems = <?= json_encode($produtosBuscaInicialVenda, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
		let serverItems = initialItems;
		let requestId = 0;
		let debounceTimer = null;
		let selectedItem = null;

		const normalizeText = (value) => {
			return String(value || '')
				.toLowerCase()
				.normalize('NFD')
				.replace(/[\u0300-\u036f]/g, '')
				.replace(/\s+/g, ' ')
				.trim();
		};

		const money = (value) => Number(value || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

		const escapeHtml = (value) => {
			return String(value ?? '')
				.replace(/&/g, '&amp;')
				.replace(/</g, '&lt;')
				.replace(/>/g, '&gt;')
				.replace(/"/g, '&quot;')
				.replace(/'/g, '&#039;');
		};

		const stockText = (stock) => {
			const total = Number(stock || 0);
			if (total <= 0) {
				return 'Sem estoque disponivel';
			}
			return `${total} un disponiveis`;
		};

		const updateNewReceitaLink = (produtoId) => {
			const params = new URLSearchParams();
			params.set('return_to', returnTo);
			if (clienteId > 0) {
				params.set('cliente_id', String(clienteId));
			}
			if (Number(produtoId) > 0) {
				params.set('produto_id', String(produtoId));
			}
			receitaLink.href = `/receitas/novo?${params.toString()}`;
		};

		const resetReceitaSection = () => {
			receitaWrap.style.display = 'none';
			receitaSelect.required = false;
			receitaSelect.disabled = true;
			receitaSelect.innerHTML = '<option value="">Selecione a receita</option>';
			receitaHelper.textContent = 'Para medicamentos com receita, selecione uma receita valida.';
		};

		const loadReceitas = async (item) => {
			if (Number(item.exige_receita) !== 1) {
				resetReceitaSection();
				return;
			}

			receitaWrap.style.display = 'block';
			receitaSelect.required = true;
			updateNewReceitaLink(item.id);

			if (clienteId <= 0) {
				receitaSelect.disabled = true;
				receitaSelect.innerHTML = '<option value="">Venda sem cliente vinculado</option>';
				receitaHelper.textContent = 'Vincule um cliente para selecionar receita.';
				return;
			}

			receitaSelect.disabled = false;
			receitaSelect.innerHTML = '<option value="">Carregando receitas...</option>';
			receitaHelper.textContent = 'Carregando receitas disponiveis...';

			const params = new URLSearchParams({
				venda_id: String(vendaId),
				produto_id: String(item.id),
			});

			try {
				const response = await fetch(`${receitasEndpoint}?${params.toString()}`, {
					headers: { 'Accept': 'application/json' },
				});

				if (!response.ok) {
					throw new Error('Falha ao carregar receitas.');
				}

				const data = await response.json();
				if (!data || data.ok !== true || !Array.isArray(data.items)) {
					throw new Error('Resposta invalida de receitas.');
				}

				if (data.items.length === 0) {
					receitaSelect.innerHTML = '<option value="">Nenhuma receita encontrada</option>';
					receitaHelper.textContent = 'Cadastre uma nova receita para continuar esta venda.';
					return;
				}

				receitaSelect.innerHTML = '<option value="">Selecione a receita</option>' + data.items.map((receita) => {
					const label = `#${Number(receita.id)} - ${receita.data_receita} - ${receita.medico_nome} (${receita.crm})`;
					return `<option value="${Number(receita.id)}">${escapeHtml(label)}</option>`;
				}).join('');
				receitaHelper.textContent = 'Selecione uma receita para continuar.';
			} catch (error) {
				receitaSelect.innerHTML = '<option value="">Falha ao carregar receitas</option>';
				receitaHelper.textContent = 'Nao foi possivel consultar receitas agora. Tente novamente ou cadastre uma nova receita.';
			}
		};

		const validateQtdAgainstStock = () => {
			if (!selectedItem) {
				inputQtd.setCustomValidity('');
				qtdHelper.textContent = 'Selecione um produto para visualizar estoque disponivel.';
				subtotalPrev.textContent = 'Subtotal previsto: R$ 0,00';
				return;
			}

			const stock = Number(selectedItem.estoque_disponivel || 0);
			const qtd = Number(inputQtd.value || 0);

			qtdHelper.textContent = `Estoque disponivel para venda: ${stockText(stock)}.`;
			subtotalPrev.textContent = `Subtotal previsto: R$ ${money((Number(selectedItem.preco_atual || 0) * Math.max(1, qtd || 0)))}`;

			if (stock > 0 && qtd > stock) {
				inputQtd.setCustomValidity('Quantidade acima do estoque disponivel para venda.');
			} else {
				inputQtd.setCustomValidity('');
			}
		};

		const clearSelection = () => {
			selectedItem = null;
			inputProdutoId.value = '';
			selectedWrap.innerHTML = '';
			selectedWrap.style.display = 'none';
			updateNewReceitaLink(0);
			resetReceitaSection();
			validateQtdAgainstStock();
		};

		const setSelection = async (item) => {
			selectedItem = item;
			inputProdutoId.value = String(item.id);
			inputSearch.value = item.nome;

			const receita = Number(item.exige_receita) === 1 ? 'Receita obrigatoria' : 'Sem obrigacao de receita';
			selectedWrap.innerHTML = `
				<span class="pill">${escapeHtml(item.nome)}</span>
				<span class="pill">R$ ${money(item.preco_atual)}</span>
				<span class="pill">${stockText(item.estoque_disponivel)}</span>
				<span class="pill">${receita}</span>
			`;
			selectedWrap.style.display = 'flex';
			list.innerHTML = '';
			status.textContent = 'Produto selecionado.';
			validateQtdAgainstStock();
			await loadReceitas(item);
		};

		const renderResults = (items) => {
			if (!Array.isArray(items) || items.length === 0) {
				list.innerHTML = '<li><button class="search-result-btn" type="button">Nenhum produto encontrado.</button></li>';
				return;
			}

			list.innerHTML = items.map((item) => {
				const receita = Number(item.exige_receita) === 1 ? 'Receita obrigatoria' : 'Receita opcional';
				return `<li>
					<button type="button" class="search-result-btn" data-id="${Number(item.id)}">
						<span>
							<strong>${escapeHtml(item.nome)}</strong><br>
							<span class="search-meta">${escapeHtml(item.principio_ativo || item.marca_laboratorio || 'Sem principio ativo informado')}</span>
						</span>
						<span class="search-meta">R$ ${money(item.preco_atual)}<br>${stockText(item.estoque_disponivel)}<br>${receita}</span>
					</button>
				</li>`;
			}).join('');
		};

		const applyLocalFilter = () => {
			const q = normalizeText(inputSearch.value);
			const filtered = serverItems.filter((item) => {
				if (q === '') {
					return true;
				}

				const searchable = normalizeText([item.nome, item.codigo_barras, item.principio_ativo, item.marca_laboratorio].join(' '));
				return searchable.includes(q);
			});

			renderResults(filtered.slice(0, 12));
			status.textContent = `${filtered.length} resultado(s) filtrados localmente.`;
		};

		const fetchFromServer = async () => {
			const q = inputSearch.value.trim();
			if (q.length < 2) {
				serverItems = initialItems;
				applyLocalFilter();
				status.textContent = 'Resultados atualizados.';
				return;
			}

			const currentRequestId = ++requestId;
			status.textContent = 'Buscando produtos...';

			const params = new URLSearchParams({ q, limit: '20', ativo: '1' });

			try {
				const response = await fetch(`${endpoint}?${params.toString()}`, {
					headers: { 'Accept': 'application/json' },
				});

				if (!response.ok) {
					throw new Error('Falha na busca');
				}

				const data = await response.json();
				if (currentRequestId !== requestId) {
					return;
				}

				if (!data || data.ok !== true || !Array.isArray(data.items)) {
					throw new Error('Resposta invalida');
				}

				serverItems = data.items;
				applyLocalFilter();
				status.textContent = `${data.total} produto(s) encontrado(s).`;
			} catch (error) {
				status.textContent = 'Falha na busca online. Mantendo busca local.';
				applyLocalFilter();
			}
		};

		const scheduleSearch = () => {
			if (debounceTimer !== null) {
				clearTimeout(debounceTimer);
			}

			clearSelection();
			applyLocalFilter();
			debounceTimer = setTimeout(fetchFromServer, 250);
		};

		inputSearch.addEventListener('input', scheduleSearch);
		inputSearch.addEventListener('focus', () => {
			if (inputSearch.value.trim() === '') {
				renderResults(serverItems.slice(0, 12));
				status.textContent = 'Sugestoes de produtos.';
			}
		});

		inputQtd.addEventListener('input', validateQtdAgainstStock);

		form.addEventListener('submit', (event) => {
			if (!inputProdutoId.value) {
				event.preventDefault();
				status.textContent = 'Selecione um produto antes de adicionar item.';
				return;
			}

			if (selectedItem && Number(selectedItem.exige_receita) === 1 && !receitaSelect.value) {
				event.preventDefault();
				receitaHelper.textContent = clienteId <= 0
					? 'Vincule um cliente para selecionar receita.'
					: 'Selecione uma receita para concluir.';
				return;
			}

			validateQtdAgainstStock();
			if (!form.checkValidity()) {
				event.preventDefault();
			}
		});

		list.addEventListener('click', (event) => {
			const button = event.target.closest('button[data-id]');
			if (!button) {
				return;
			}

			const id = Number(button.getAttribute('data-id'));
			const item = serverItems.find((produto) => Number(produto.id) === id) || initialItems.find((produto) => Number(produto.id) === id);
			if (!item) {
				return;
			}

			setSelection(item);
		});

		updateNewReceitaLink(0);
		resetReceitaSection();
		renderResults(initialItems.slice(0, 12));
	})();
	</script>
<?php endif; ?>

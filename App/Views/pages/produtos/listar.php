<div class="card">
	<div class="section-head">
		<h2>Produtos</h2>
		<a class="btn-inline" href="/produtos/novo">Novo produto</a>
	</div>
	<p class="muted">Busca hibrida por nome, codigo de barras, principio ativo e laboratorio.</p>

	<div class="search-panel">
		<div class="search-grid">
			<input id="produto-search-q" placeholder="Buscar por nome, codigo ou principio ativo" autocomplete="off">
			<select id="produto-search-tipo">
				<option value="">Todos os tipos</option>
				<option value="generico">Generico</option>
				<option value="similar">Similar</option>
				<option value="referencia">Referencia</option>
			</select>
			<select id="produto-search-receita">
				<option value="">Receita: todos</option>
				<option value="1">Com receita</option>
				<option value="0">Sem receita</option>
			</select>
		</div>
		<p class="helper-text" id="produto-search-status">Mostrando catalogo inicial.</p>
	</div>

	<div class="table-wrap">
		<table>
			<thead>
				<tr>
					<th>ID</th>
					<th>Nome</th>
					<th>Tipo</th>
					<th>Receita</th>
					<th>Estoque</th>
					<th>Preco</th>
					<th>Codigo barras</th>
				</tr>
			</thead>
			<tbody id="produto-list-body">
				<?php if (empty($produtos)): ?>
					<tr><td colspan="7">Nenhum produto cadastrado.</td></tr>
				<?php else: ?>
					<?php foreach ($produtos as $produto): ?>
						<tr>
							<td><?= (int) $produto['id'] ?></td>
							<td><?= htmlspecialchars($produto['nome'], ENT_QUOTES, 'UTF-8') ?></td>
							<td><?= htmlspecialchars($produto['tipo'], ENT_QUOTES, 'UTF-8') ?></td>
							<td><?= ((int) $produto['exige_receita'] === 1) ? 'Sim' : 'Nao' ?></td>
							<td><?= (int) ($produto['estoque_disponivel'] ?? 0) ?></td>
							<td>R$ <?= number_format((float) $produto['preco_atual'], 2, ',', '.') ?></td>
							<td><?= htmlspecialchars($produto['codigo_barras'], ENT_QUOTES, 'UTF-8') ?></td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
</div>

<?php
$produtosBuscaInicial = [];
foreach ($produtos as $produto) {
	$produtosBuscaInicial[] = [
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
	const inputQ = document.getElementById('produto-search-q');
	const inputTipo = document.getElementById('produto-search-tipo');
	const inputReceita = document.getElementById('produto-search-receita');
	const tbody = document.getElementById('produto-list-body');
	const status = document.getElementById('produto-search-status');

	if (!inputQ || !inputTipo || !inputReceita || !tbody || !status) {
		return;
	}

	const initialItems = <?= json_encode($produtosBuscaInicial, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
	let serverItems = initialItems;
	let requestId = 0;
	let debounceTimer = null;

	const normalizeText = (value) => {
		return String(value || '')
			.toLowerCase()
			.normalize('NFD')
			.replace(/[\u0300-\u036f]/g, '')
			.replace(/\s+/g, ' ')
			.trim();
	};

	const escapeHtml = (value) => {
		return String(value ?? '')
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#039;');
	};

	const formatCurrency = (value) => {
		const number = Number(value || 0);
		return number.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
	};

	const renderRows = (items) => {
		if (!Array.isArray(items) || items.length === 0) {
			tbody.innerHTML = '<tr><td colspan="7">Nenhum produto encontrado.</td></tr>';
			return;
		}

		tbody.innerHTML = items.map((item) => {
			const receita = Number(item.exige_receita) === 1 ? 'Sim' : 'Nao';
			return `<tr>
				<td>${Number(item.id)}</td>
				<td>${escapeHtml(item.nome)}</td>
				<td>${escapeHtml(item.tipo)}</td>
				<td>${receita}</td>
				<td>${Number(item.estoque_disponivel || 0)}</td>
				<td>R$ ${formatCurrency(item.preco_atual)}</td>
				<td>${escapeHtml(item.codigo_barras)}</td>
			</tr>`;
		}).join('');
	};

	const applyLocalFilter = () => {
		const qNorm = normalizeText(inputQ.value);
		const tipo = inputTipo.value;
		const receita = inputReceita.value;

		const filtered = serverItems.filter((item) => {
			if (tipo !== '' && item.tipo !== tipo) {
				return false;
			}

			if (receita !== '' && String(item.exige_receita) !== receita) {
				return false;
			}

			if (qNorm === '') {
				return true;
			}

			const joined = normalizeText([item.nome, item.codigo_barras, item.principio_ativo, item.marca_laboratorio].join(' '));
			return joined.includes(qNorm);
		});

		renderRows(filtered);
		status.textContent = `${filtered.length} resultado(s) exibidos.`;
	};

	const fetchFromServer = async () => {
		const q = inputQ.value.trim();
		const tipo = inputTipo.value;
		const receita = inputReceita.value;

		if (q.length < 2 && tipo === '' && receita === '') {
			serverItems = initialItems;
			applyLocalFilter();
			status.textContent = 'Mostrando catalogo inicial.';
			return;
		}

		const currentRequestId = ++requestId;
		status.textContent = 'Buscando no banco...';

		const params = new URLSearchParams({ limit: '30', ativo: '1' });
		if (q.length > 0) {
			params.set('q', q);
		}
		if (tipo !== '') {
			params.set('tipo', tipo);
		}
		if (receita !== '') {
			params.set('exige_receita', receita);
		}

		try {
			const response = await fetch(`${endpoint}?${params.toString()}`, {
				headers: { 'Accept': 'application/json' },
			});
			if (!response.ok) {
				throw new Error('Falha no endpoint de busca');
			}

			const data = await response.json();
			if (currentRequestId !== requestId) {
				return;
			}

			if (!data || data.ok !== true || !Array.isArray(data.items)) {
				throw new Error('Resposta invalida da busca');
			}

			serverItems = data.items;
			applyLocalFilter();
			status.textContent = `Banco retornou ${data.total} registro(s).`;
		} catch (error) {
			status.textContent = 'Falha ao buscar no banco. Exibindo dados locais.';
			applyLocalFilter();
		}
	};

	const scheduleServerSearch = () => {
		if (debounceTimer !== null) {
			clearTimeout(debounceTimer);
		}

		applyLocalFilter();
		debounceTimer = setTimeout(fetchFromServer, 250);
	};

	inputQ.addEventListener('input', scheduleServerSearch);
	inputTipo.addEventListener('change', scheduleServerSearch);
	inputReceita.addEventListener('change', scheduleServerSearch);

	renderRows(initialItems);
})();
</script>

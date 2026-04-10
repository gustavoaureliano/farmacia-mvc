<?php $baseUrl = (string) ($GLOBALS['BASE_URL'] ?? ''); ?>
<div class="card">
	<div class="section-head">
		<h2>Produtos</h2>
		<a class="btn-inline" href="<?= htmlspecialchars($baseUrl . '/produtos/novo', ENT_QUOTES, 'UTF-8') ?>">Novo produto</a>
	</div>
	<p class="muted">Pesquise e filtre produtos por nome, codigo de barras, principio ativo e laboratorio.</p>

	<div class="search-panel">
		<div class="search-grid" style="grid-template-columns: minmax(280px, 2fr) repeat(3, minmax(140px, 1fr));">
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
			<select id="produto-search-ordem">
				<option value="relevancia" selected>Ordem: padrao</option>
				<option value="nome_asc">Nome A-Z</option>
				<option value="preco_asc">Menor preco</option>
				<option value="preco_desc">Maior preco</option>
				<option value="estoque_desc">Maior estoque</option>
				<option value="codigo_asc">Codigo crescente</option>
			</select>
		</div>
		<div class="pills" style="margin-top: 10px;">
			<button type="button" id="produto-search-limpar" class="btn-subtle">Limpar filtros</button>
		</div>
		<p class="helper-text" id="produto-search-status" aria-live="polite">Lista inicial carregada.</p>
	</div>

	<div class="table-wrap">
		<table>
			<thead>
				<tr>
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
					<tr><td colspan="6">Nenhum produto cadastrado.</td></tr>
				<?php else: ?>
					<?php foreach ($produtos as $produto): ?>
						<tr>
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
		'id' => (string) $produto['codigo_barras'],
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
	const baseUrl = <?= json_encode($baseUrl, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
	const endpoint = `${baseUrl}/produtos/buscar`;
	const inputQ = document.getElementById('produto-search-q');
	const inputTipo = document.getElementById('produto-search-tipo');
	const inputReceita = document.getElementById('produto-search-receita');
	const inputOrdem = document.getElementById('produto-search-ordem');
	const btnLimpar = document.getElementById('produto-search-limpar');
	const tbody = document.getElementById('produto-list-body');
	const status = document.getElementById('produto-search-status');

	if (!inputQ || !inputTipo || !inputReceita || !inputOrdem || !btnLimpar || !tbody || !status) {
		return;
	}

	const STORAGE_Q = 'produtosFiltroQ';
	const STORAGE_TIPO = 'produtosFiltroTipo';
	const STORAGE_RECEITA = 'produtosFiltroReceita';
	const STORAGE_ORDEM = 'produtosFiltroOrdem';

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

	const orderItems = (items, order) => {
		const sorted = [...items];

		sorted.sort((a, b) => {
			if (order === 'nome_asc') {
				return String(a.nome || '').localeCompare(String(b.nome || ''), 'pt-BR');
			}

			if (order === 'preco_asc') {
				return Number(a.preco_atual || 0) - Number(b.preco_atual || 0);
			}

			if (order === 'preco_desc') {
				return Number(b.preco_atual || 0) - Number(a.preco_atual || 0);
			}

			if (order === 'estoque_desc') {
				return Number(b.estoque_disponivel || 0) - Number(a.estoque_disponivel || 0);
			}

			if (order === 'codigo_asc') {
				return String(a.codigo_barras || '').localeCompare(String(b.codigo_barras || ''), 'pt-BR');
			}

			return 0;
		});

		return sorted;
	};

	const renderRows = (items) => {
		if (!Array.isArray(items) || items.length === 0) {
			tbody.innerHTML = '<tr><td colspan="6">Nenhum produto encontrado.</td></tr>';
			return;
		}

			tbody.innerHTML = items.map((item) => {
			const receita = Number(item.exige_receita) === 1 ? 'Sim' : 'Nao';
			return `<tr>
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
		const ordem = inputOrdem.value || 'relevancia';

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

		const ordered = orderItems(filtered, ordem);
		renderRows(ordered);
		status.textContent = `${ordered.length} produto(s) exibido(s).`;

		localStorage.setItem(STORAGE_Q, inputQ.value);
		localStorage.setItem(STORAGE_TIPO, tipo);
		localStorage.setItem(STORAGE_RECEITA, receita);
		localStorage.setItem(STORAGE_ORDEM, ordem);
	};

	const fetchFromServer = async () => {
		const q = inputQ.value.trim();
		const tipo = inputTipo.value;
		const receita = inputReceita.value;

		if (q.length < 2 && tipo === '' && receita === '') {
			serverItems = initialItems;
			applyLocalFilter();
			status.textContent = 'Lista inicial carregada.';
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
				throw new Error('Falha na busca de produtos');
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
			status.textContent = `${data.total} produto(s) encontrado(s).`;
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
	inputOrdem.addEventListener('change', applyLocalFilter);
	btnLimpar.addEventListener('click', () => {
		inputQ.value = '';
		inputTipo.value = '';
		inputReceita.value = '';
		inputOrdem.value = 'relevancia';
		serverItems = initialItems;

		localStorage.removeItem(STORAGE_Q);
		localStorage.removeItem(STORAGE_TIPO);
		localStorage.removeItem(STORAGE_RECEITA);
		localStorage.removeItem(STORAGE_ORDEM);

		applyLocalFilter();
		status.textContent = 'Filtros limpos.';
	});

	const savedQ = localStorage.getItem(STORAGE_Q);
	const savedTipo = localStorage.getItem(STORAGE_TIPO);
	const savedReceita = localStorage.getItem(STORAGE_RECEITA);
	const savedOrdem = localStorage.getItem(STORAGE_ORDEM);

	if (savedQ !== null) {
		inputQ.value = savedQ;
	}

	if (savedTipo !== null && Array.from(inputTipo.options).some((opt) => opt.value === savedTipo)) {
		inputTipo.value = savedTipo;
	}

	if (savedReceita !== null && Array.from(inputReceita.options).some((opt) => opt.value === savedReceita)) {
		inputReceita.value = savedReceita;
	}

	if (savedOrdem !== null && Array.from(inputOrdem.options).some((opt) => opt.value === savedOrdem)) {
		inputOrdem.value = savedOrdem;
	}

	if (inputQ.value.trim().length >= 2 || inputTipo.value !== '' || inputReceita.value !== '') {
		scheduleServerSearch();
	} else {
		applyLocalFilter();
	}
})();
</script>

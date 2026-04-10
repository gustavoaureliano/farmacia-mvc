<?php $baseUrl = (string) ($GLOBALS['BASE_URL'] ?? ''); ?>
<div class="card dashboard-hero">
	<span class="badge">OPERACAO INTERNA</span>
	<h2>Painel do Balcao</h2>
	<p class="muted">Sistema interno para cadastro e vendas da farmacia, com foco em agilidade no atendimento.</p>
	<p class="helper-text">Use os atalhos para acelerar as tarefas mais frequentes no dia a dia.</p>
	<div class="quick-actions">
		<a href="<?= htmlspecialchars($baseUrl . '/vendas/nova', ENT_QUOTES, 'UTF-8') ?>">Iniciar venda</a>
		<a href="<?= htmlspecialchars($baseUrl . '/produtos', ENT_QUOTES, 'UTF-8') ?>">Buscar produtos</a>
		<a href="<?= htmlspecialchars($baseUrl . '/estoque', ENT_QUOTES, 'UTF-8') ?>">Gerenciar estoque</a>
		<a href="<?= htmlspecialchars($baseUrl . '/receitas', ENT_QUOTES, 'UTF-8') ?>">Gerenciar receitas</a>
		<a href="<?= htmlspecialchars($baseUrl . '/produtos/novo', ENT_QUOTES, 'UTF-8') ?>">Cadastrar produto</a>
		<a href="<?= htmlspecialchars($baseUrl . '/clientes', ENT_QUOTES, 'UTF-8') ?>">Ver clientes</a>
		<a href="<?= htmlspecialchars($baseUrl . '/funcionarios', ENT_QUOTES, 'UTF-8') ?>">Equipe</a>
	</div>
</div>

<div class="card">
	<h3>Fluxo recomendado</h3>
	<div class="pills">
		<span class="pill">1. Iniciar venda</span>
		<span class="pill">2. Pesquisar produto</span>
		<span class="pill">3. Validar receita</span>
		<span class="pill">4. Adicionar item a venda</span>
	</div>
</div>

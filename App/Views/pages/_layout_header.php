<!DOCTYPE html>
<html lang="pt-BR">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Farmacia MVC</title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
	<style>
		:root {
			--bg: #edf3f8;
			--surface: #ffffff;
			--surface-soft: #f7fbff;
			--surface-strong: #0f2d45;
			--text: #112033;
			--text-soft: #4a6177;
			--line: #d7e2ed;
			--accent: #1579cc;
			--accent-strong: #0a5aa1;
			--accent-soft: #e7f3ff;
			--ok-bg: #dcfce7;
			--ok-text: #166534;
			--err-bg: #fee2e2;
			--err-text: #991b1b;
			--radius-lg: 18px;
			--radius-md: 12px;
			--shadow: 0 16px 36px rgba(16, 40, 70, 0.08);
		}

		* { box-sizing: border-box; }

		body {
			margin: 0;
			font-family: "Manrope", "Segoe UI", sans-serif;
			background: radial-gradient(circle at 88% 6%, rgba(21, 121, 204, 0.18), transparent 36%), radial-gradient(circle at 8% 28%, rgba(31, 194, 212, 0.16), transparent 34%), var(--bg);
			color: var(--text);
			min-height: 100vh;
		}

		a { color: var(--accent-strong); }

		header {
			position: sticky;
			top: 0;
			z-index: 20;
			background: linear-gradient(130deg, #0f2d45, #145076 58%, #1579cc);
			color: #fff;
			padding: 18px 24px;
			border-bottom: 1px solid rgba(255, 255, 255, 0.2);
			box-shadow: 0 14px 30px rgba(8, 30, 52, 0.28);
		}

		.header-row {
			display: flex;
			align-items: center;
			justify-content: space-between;
			gap: 16px;
			max-width: 1200px;
			margin: 0 auto;
		}

		.brand {
			display: flex;
			flex-direction: column;
			gap: 4px;
		}

		.brand h1 {
			margin: 0;
			font-family: "Sora", "Manrope", sans-serif;
			font-size: 1.5rem;
			line-height: 1.1;
		}

		.brand p {
			margin: 0;
			font-size: 0.86rem;
			opacity: 0.88;
		}

		nav {
			display: flex;
			flex-wrap: wrap;
			gap: 10px;
		}

		nav a {
			display: inline-flex;
			align-items: center;
			padding: 8px 12px;
			border-radius: 999px;
			text-decoration: none;
			font-weight: 700;
			font-size: 0.92rem;
			color: #fff;
			border: 1px solid rgba(255, 255, 255, 0.3);
			transition: transform 0.18s ease, background-color 0.18s ease;
		}

		nav a:hover {
			background: rgba(255, 255, 255, 0.16);
			transform: translateY(-1px);
		}

		main {
			max-width: 1200px;
			margin: 24px auto;
			padding: 0 16px 28px;
		}

		.card {
			background: linear-gradient(170deg, #ffffff, #f9fcff 80%);
			border: 1px solid var(--line);
			border-radius: var(--radius-lg);
			padding: 18px;
			margin-bottom: 16px;
			box-shadow: var(--shadow);
			animation: card-enter 0.32s ease;
		}

		@keyframes card-enter {
			from {
				opacity: 0;
				transform: translateY(6px);
			}
			to {
				opacity: 1;
				transform: translateY(0);
			}
		}

		.card h2,
		.card h3 {
			font-family: "Sora", "Manrope", sans-serif;
			margin-top: 0;
		}

		.section-head {
			display: flex;
			align-items: center;
			justify-content: space-between;
			gap: 14px;
			margin-bottom: 10px;
		}

		.muted {
			color: var(--text-soft);
			margin-top: 0;
		}

		.btn-inline,
		button,
		.button-link {
			display: inline-flex;
			align-items: center;
			justify-content: center;
			padding: 10px 14px;
			border-radius: 11px;
			text-decoration: none;
			font-weight: 700;
			border: 0;
			cursor: pointer;
			transition: transform 0.16s ease, box-shadow 0.16s ease, background-color 0.16s ease;
			background: linear-gradient(120deg, #1579cc, #0f5fae 55%, #0f9ac5);
			color: #fff;
			box-shadow: 0 8px 20px rgba(13, 86, 153, 0.25);
		}

		.btn-soft {
			display: inline-flex;
			align-items: center;
			justify-content: center;
			padding: 9px 12px;
			border-radius: 10px;
			text-decoration: none;
			font-weight: 700;
			border: 1px solid #c7d9ea;
			background: #f5fbff;
			color: #0f4f8d;
		}

		.btn-soft:hover {
			background: #e7f3ff;
		}

		.btn-subtle {
			display: inline-flex;
			align-items: center;
			justify-content: center;
			padding: 8px 10px;
			border-radius: 10px;
			text-decoration: none;
			font-weight: 600;
			font-size: 0.9rem;
			border: 1px solid #d7e4ef;
			background: #f8fbfe;
			color: #5b7085;
			cursor: pointer;
			box-shadow: none;
			transform: none;
		}

		.btn-subtle:hover {
			background: #f1f6fb;
			color: #425a72;
			box-shadow: none;
			transform: none;
		}

		.btn-inline:hover,
		button:hover,
		.button-link:hover {
			transform: translateY(-1px);
			box-shadow: 0 11px 22px rgba(12, 73, 130, 0.28);
		}

		button.btn-subtle,
		button.btn-subtle:hover {
			transform: none;
			box-shadow: none;
		}

		.table-wrap {
			overflow-x: auto;
			border: 1px solid var(--line);
			border-radius: 12px;
		}

		table {
			width: 100%;
			border-collapse: collapse;
			background: var(--surface);
			min-width: 640px;
		}

		th, td {
			border-bottom: 1px solid var(--line);
			padding: 12px 10px;
			text-align: left;
		}

		th {
			background: var(--surface-soft);
			font-size: 0.86rem;
			text-transform: uppercase;
			letter-spacing: 0.04em;
			color: #294661;
		}

		tbody tr:nth-child(even) { background: #fbfdff; }

		form { display: grid; gap: 11px; }

		label {
			font-weight: 700;
			font-size: 0.92rem;
		}

		input,
		select,
		textarea {
			padding: 10px;
			border: 1px solid #c6d5e4;
			border-radius: 10px;
			font: inherit;
			background: #fff;
			color: var(--text);
		}

		input:focus,
		select:focus,
		textarea:focus {
			outline: none;
			border-color: #3097e5;
			box-shadow: 0 0 0 3px rgba(48, 151, 229, 0.22);
		}

		.search-panel {
			background: var(--surface-soft);
			border: 1px solid #d4e4f3;
			border-radius: 14px;
			padding: 12px;
			margin-bottom: 14px;
		}

		.search-grid {
			display: grid;
			grid-template-columns: minmax(280px, 2fr) repeat(2, minmax(160px, 1fr));
			gap: 10px;
		}

		.search-result-list {
			margin: 0;
			padding: 0;
			list-style: none;
			border: 1px solid #d6e4f0;
			border-radius: 12px;
			max-height: 260px;
			overflow: auto;
			background: #fff;
		}

		.search-result-list li + li {
			border-top: 1px solid #e5edf4;
		}

		.search-result-btn {
			width: 100%;
			padding: 10px;
			text-align: left;
			border: 0;
			background: #fff;
			color: var(--text);
			box-shadow: none;
			cursor: pointer;
			display: flex;
			justify-content: space-between;
			gap: 10px;
			border-radius: 0;
			font-weight: 600;
		}

		.search-result-btn:hover {
			background: #eef7ff;
		}

		.search-meta {
			font-size: 0.86rem;
			color: var(--text-soft);
		}

		.pills {
			display: flex;
			flex-wrap: wrap;
			gap: 8px;
			margin-top: 6px;
		}

		.pill {
			display: inline-flex;
			padding: 4px 10px;
			border-radius: 999px;
			background: var(--accent-soft);
			color: var(--accent-strong);
			font-size: 0.82rem;
			font-weight: 700;
		}

		.pill-critico {
			background: #fee2e2;
			color: #991b1b;
		}

		.pill-atencao {
			background: #fff4d4;
			color: #9a6700;
		}

		.pill-ok {
			background: #dcfce7;
			color: #166534;
		}

		.pill-sem-estoque {
			background: #e2e8f0;
			color: #334155;
		}

		.summary-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
			gap: 10px;
			margin-bottom: 14px;
		}

		.summary-card {
			background: #fff;
			border: 1px solid #dbe7f2;
			border-radius: 12px;
			padding: 12px;
		}

		.summary-card h4 {
			margin: 0 0 4px;
		}

		.summary-card p {
			margin: 0;
			color: var(--text-soft);
		}

		.msg-ok,
		.msg-err {
			padding: 11px 12px;
			border-radius: 11px;
			margin-bottom: 14px;
			font-weight: 600;
		}

		.msg-ok {
			background: var(--ok-bg);
			color: var(--ok-text);
			border: 1px solid #b8ebca;
		}

		.msg-err {
			background: var(--err-bg);
			color: var(--err-text);
			border: 1px solid #f5c3c3;
		}

		.quick-actions {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
			gap: 10px;
			margin-top: 12px;
		}

		.quick-actions a {
			background: #fff;
			border: 1px solid #d3e1ee;
			border-radius: 12px;
			padding: 12px;
			text-decoration: none;
			font-weight: 700;
			color: #17456b;
			box-shadow: 0 8px 18px rgba(14, 72, 120, 0.08);
		}

		.quick-actions a:hover {
			background: #eff7ff;
		}

		.dashboard-hero {
			display: grid;
			gap: 10px;
		}

		.badge {
			display: inline-flex;
			align-items: center;
			gap: 6px;
			padding: 5px 11px;
			border-radius: 999px;
			font-size: 0.78rem;
			font-weight: 800;
			color: #0f4f8d;
			background: linear-gradient(120deg, #d7ecff, #d8fbff);
			border: 1px solid #bbdbf3;
			width: fit-content;
		}

		.helper-text {
			font-size: 0.85rem;
			color: var(--text-soft);
		}

		@media (max-width: 920px) {
			.header-row {
				flex-direction: column;
				align-items: flex-start;
			}

			.search-grid {
				grid-template-columns: 1fr;
			}
		}

		@media (max-width: 620px) {
			header {
				padding: 16px;
			}

			main {
				padding: 0 10px 24px;
				margin-top: 16px;
			}

			.card {
				padding: 14px;
			}

			table {
				min-width: 560px;
			}

			nav {
				width: 100%;
			}

			nav a {
				flex: 1;
				justify-content: center;
			}
		}
	</style>
</head>
<body>
<header>
	<div class="header-row">
		<div class="brand">
			<h1>Farmacia MVC</h1>
			<p>Balcao interno com foco em agilidade de atendimento</p>
		</div>
		<nav>
			<a href="/home">Home</a>
			<a href="/produtos">Produtos</a>
			<a href="/lotes">Lotes</a>
			<a href="/receitas">Receitas</a>
			<a href="/clientes">Clientes</a>
			<a href="/funcionarios">Funcionarios</a>
			<a href="/vendas/nova">Vendas</a>
		</nav>
	</div>
</header>
<main>
<?php if (!empty($_SESSION['flash_success'])): ?>
	<div class="msg-ok"><?= htmlspecialchars($_SESSION['flash_success'], ENT_QUOTES, 'UTF-8') ?></div>
	<?php unset($_SESSION['flash_success']); ?>
<?php endif; ?>
<?php if (!empty($_SESSION['flash_error'])): ?>
	<div class="msg-err"><?= htmlspecialchars($_SESSION['flash_error'], ENT_QUOTES, 'UTF-8') ?></div>
	<?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>

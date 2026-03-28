<!DOCTYPE html>
<html lang="pt-BR">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Farmacia MVC</title>
	<style>
		body { font-family: "DejaVu Sans", sans-serif; margin: 0; background: #f5f7fb; color: #18202b; }
		header { background: #0f5132; color: #fff; padding: 16px 24px; }
		nav a { color: #fff; margin-right: 16px; text-decoration: none; font-weight: 600; }
		main { max-width: 1024px; margin: 24px auto; padding: 0 16px; }
		.card { background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px; margin-bottom: 16px; }
		table { width: 100%; border-collapse: collapse; }
		th, td { border-bottom: 1px solid #e5e7eb; padding: 10px 8px; text-align: left; }
		form { display: grid; gap: 10px; }
		input, select, button { padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px; }
		button { background: #0f5132; color: white; border: 0; cursor: pointer; }
		.msg-ok { background: #dcfce7; color: #166534; padding: 10px; border-radius: 8px; margin-bottom: 16px; }
		.msg-err { background: #fee2e2; color: #991b1b; padding: 10px; border-radius: 8px; margin-bottom: 16px; }
	</style>
</head>
<body>
<header>
	<h1 style="margin: 0 0 8px;">Farmacia MVC</h1>
	<nav>
		<a href="/home">Home</a>
		<a href="/produtos">Produtos</a>
		<a href="/clientes">Clientes</a>
		<a href="/funcionarios">Funcionarios</a>
		<a href="/vendas/nova">Vendas</a>
	</nav>
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

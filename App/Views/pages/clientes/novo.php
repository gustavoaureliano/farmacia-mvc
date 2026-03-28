<div class="card">
	<h2>Novo cliente</h2>
	<form method="POST" action="/clientes/salvar">
		<input name="nome" placeholder="Nome" required>
		<input name="cpf" placeholder="CPF (somente numeros)" maxlength="11" required>
		<input type="date" name="data_nascimento" placeholder="Data nascimento">
		<input name="telefone" placeholder="Telefone">
		<button type="submit">Salvar</button>
	</form>
</div>

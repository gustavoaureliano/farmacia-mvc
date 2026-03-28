<div class="card">
	<h2>Novo produto</h2>
	<form method="POST" action="/produtos/salvar">
		<input name="nome" placeholder="Nome" required>
		<input name="principio_ativo" placeholder="Principio ativo">
		<input name="marca_laboratorio" placeholder="Marca ou laboratorio">
		<select name="tipo" required>
			<option value="generico">Generico</option>
			<option value="similar">Similar</option>
			<option value="referencia">Referencia</option>
		</select>
		<label><input type="checkbox" name="exige_receita" value="1"> Exige receita</label>
		<input type="number" step="0.01" min="0.01" name="preco_atual" placeholder="Preco" required>
		<input name="codigo_barras" placeholder="Codigo de barras" required>
		<button type="submit">Salvar</button>
	</form>
</div>

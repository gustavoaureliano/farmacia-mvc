USE farmacia_db;

INSERT INTO funcionarios (nome, cargo, cpf, crf) VALUES
('Maria Silva', 'farmaceutico', '11111111111', 'CRF12345'),
('Joao Costa', 'atendente', '22222222222', NULL);

INSERT INTO clientes (nome, cpf, data_nascimento, telefone) VALUES
('Ana Pereira', '33333333333', '1991-05-10', '11999999999'),
('Carlos Souza', '44444444444', '1987-08-17', '11888888888');

INSERT INTO produtos (nome, principio_ativo, marca_laboratorio, tipo, exige_receita, preco_atual, codigo_barras) VALUES
('Dipirona 500mg', 'Dipirona sodica', 'Farmaco SA', 'generico', 0, 7.90, '789100000001'),
('Amoxicilina 500mg', 'Amoxicilina', 'Laboratorio Beta', 'referencia', 1, 32.50, '789100000002');

INSERT INTO lotes_estoque (produto_id, numero_lote, validade, quantidade_disponivel, localizacao) VALUES
(1, 'DIP-001', '2027-12-31', 100, 'Prateleira A1'),
(1, 'DIP-002', '2026-11-30', 50, 'Prateleira A2'),
(2, 'AMX-010', '2026-06-30', 40, 'Prateleira B1');

INSERT INTO receitas (cliente_id, medico_nome, crm, data_receita, observacoes) VALUES
(1, 'Dra Paula Mendes', 'CRM123456', CURDATE(), 'Uso por 7 dias');

INSERT INTO receita_itens (receita_id, produto_id, posologia) VALUES
(1, 2, '1 capsula a cada 8 horas');

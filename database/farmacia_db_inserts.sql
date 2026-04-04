USE farmacia_db;

-- Inserção de dados na tabela Cliente
INSERT INTO Cliente (cpf, nome, data_nascimento, telefone) VALUES
('111.222.333-44', 'Maria Silva', '1985-03-15', '(11) 98765-4321'),
('555.666.777-88', 'João Santos', '1990-07-20', '(21) 99887-7665'),
('999.888.777-66', 'Ana Costa', '1978-11-01', '(31) 97766-5544');

-- Inserção de dados na tabela Funcionario
INSERT INTO Funcionario (cpf, nome, cargo, registro_profissional) VALUES
('123.456.789-00', 'Carlos Pereira', 'Farmacêutico', 'CRF/SP 12345'),
('987.654.321-00', 'Fernanda Lima', 'Atendente', NULL);

-- Inserção de dados na tabela Medico
INSERT INTO Medico (crm, nome) VALUES
('CRM/SP 123456', 'Dr. Ricardo Almeida'),
('CRM/RJ 789012', 'Dra. Patricia Mendes');

-- Inserção de dados na tabela Produto
INSERT INTO Produto (cod_barras, marca, nome, tipo, precisa_receita, preco) VALUES
('7891234567890', 'Bayer', 'Aspirina 500mg', 'Analgésico', FALSE, 15.50),
('7890987654321', 'Pfizer', 'Amoxicilina 500mg', 'Antibiótico', TRUE, 45.90),
('7894561237890', 'EMS', 'Dorflex', 'Relaxante Muscular', FALSE, 12.00),
('7891122334455', 'Medley', 'Dipirona 1g', 'Analgésico', FALSE, 8.75),
('7896677889900', 'Roche', 'Rivotril 2mg', 'Ansiolítico', TRUE, 60.00);

-- Inserção de dados na tabela Estoque
INSERT INTO Estoque (cod_barras, lote, quantidade_disponivel, data_validade, localizacao) VALUES
('7891234567890', 'LOTE001', 100, '2025-12-31', 'Prateleira A1'),
('7891234567890', 'LOTE002', 50, '2026-06-30', 'Prateleira A1'),
('7890987654321', 'LOTEABC', 30, '2024-10-15', 'Refrigerador B2'),
('7894561237890', 'LOTEXYZ', 200, '2027-01-01', 'Prateleira C3'),
('7891122334455', 'LOTE123', 150, '2025-09-20', 'Prateleira A2'),
('7896677889900', 'LOTE456', 20, '2024-11-05', 'Armário Seg. D1');

-- Inserção de dados na tabela Venda
INSERT INTO Venda (data, cpf_cliente, cpf_funcionario, valor_total) VALUES
('2026-04-04 10:30:00', '111.222.333-44', '123.456.789-00', 27.50),
('2026-04-04 11:00:00', '555.666.777-88', '987.654.321-00', 45.90),
('2026-04-04 14:15:00', NULL, '123.456.789-00', 12.00);

-- Inserção de dados na tabela Receita
INSERT INTO Receita (data, crm_medico, cpf_cliente) VALUES
('2026-04-03', 'CRM/SP 123456', '555.666.777-88'),
('2026-04-02', 'CRM/RJ 789012', '111.222.333-44');

-- Inserção de dados na tabela Item_Receita
INSERT INTO Item_Receita (id_receita, cod_barras, quantidade, observacoes) VALUES
(1, '7890987654321', 1, 'Tomar 1 comprimido a cada 8 horas por 7 dias.'),
(2, '7896677889900', 1, 'Tomar 1 comprimido antes de dormir.');

-- Inserção de dados na tabela Item_Venda (assumindo id_venda gerados automaticamente)
INSERT INTO Item_Venda (id_venda, cod_barras, lote, quantidade, preco_venda, id_receita) VALUES
(1, '7891234567890', 'LOTE001', 1, 15.50, NULL),
(1, '7894561237890', 'LOTEXYZ', 1, 12.00, NULL),
(2, '7890987654321', 'LOTEABC', 1, 45.90, 1),
(3, '7894561237890', 'LOTEXYZ', 1, 12.00, NULL);

INSERT INTO Uso_Receita (id_receita, id_venda) VALUES
(1, 2);

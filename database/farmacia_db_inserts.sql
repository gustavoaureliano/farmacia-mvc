USE farmacia_db;

-- Inserção de dados na tabela Cliente
INSERT INTO Cliente (cpf, nome, data_nascimento, telefone) VALUES
('111.222.333-44', 'Maria Silva', '1985-03-15', '(11) 98765-4321'),
('555.666.777-88', 'João Santos', '1990-07-20', '(21) 99887-7665'),
('999.888.777-66', 'Ana Costa', '1978-11-01', '(31) 97766-5544'),
('101.101.101-11', 'Lucas Moraes', '1995-02-10', '(11) 91111-2222'),
('202.202.202-22', 'Beatriz Rocha', '1988-08-25', '(11) 92222-3333'),
('303.303.303-33', 'Carlos Eduardo', '2000-12-05', '(11) 93333-4444'),
('404.404.404-44', 'Mariana Farias', '1975-04-30', '(11) 94444-5555'),
('505.505.505-55', 'Roberto Alves', '1960-10-12', '(11) 95555-6666'),
('606.606.606-66', 'Juliana Castro', '1992-06-18', '(11) 96666-7777'),
('707.707.707-77', 'Marcos Teixeira', '1983-09-09', '(11) 97777-8888'),
('808.808.808-88', 'Paula Nogueira', '1997-01-21', '(11) 98888-9999');

-- Inserção de dados na tabela Funcionario
INSERT INTO Funcionario (cpf, nome, cargo, registro_profissional) VALUES
('123.456.789-00', 'Carlos Pereira', 'Farmacêutico', 'CRF/SP 12345'),
('987.654.321-00', 'Fernanda Lima', 'Atendente', NULL),
('111.000.111-00', 'Roberta Gomes', 'Atendente', NULL),
('222.000.222-00', 'Thiago Silva', 'Farmacêutico', 'CRF/SP 54321'),
('333.000.333-00', 'Amanda Oliveira', 'Atendente', NULL),
('444.000.444-00', 'Felipe Santos', 'Gerente', NULL),
('555.000.555-00', 'Camila Martins', 'Farmacêutico', 'CRF/SP 98765'),
('666.000.666-00', 'Bruno Costa', 'Atendente', NULL),
('777.000.777-00', 'Leticia Ribeiro', 'Atendente', NULL),
('888.000.888-00', 'Pedro Henrique', 'Entregador', NULL);

-- Inserção de dados na tabela Medico
INSERT INTO Medico (crm, nome) VALUES
('CRM/SP 123456', 'Dr. Ricardo Almeida'),
('CRM/RJ 789012', 'Dra. Patricia Mendes'),
('CRM/SP 111222', 'Dr. Antonio Viana'),
('CRM/SP 333444', 'Dra. Julia Nogueira'),
('CRM/MG 555666', 'Dr. Marcelo Freitas'),
('CRM/SP 777888', 'Dra. Vanessa Ramos'),
('CRM/PR 999000', 'Dr. Roberto Assis'),
('CRM/SP 234567', 'Dra. Carla Peixoto'),
('CRM/RJ 345678', 'Dr. Leonardo Diniz'),
('CRM/SP 456789', 'Dra. Fernanda Alves');

-- Inserção de dados na tabela Produto
INSERT INTO Produto (cod_barras, marca, nome, tipo, precisa_receita, preco) VALUES
('7891234567890', 'Bayer', 'Aspirina 500mg', 'Analgésico', FALSE, 15.50),
('7890987654321', 'Pfizer', 'Amoxicilina 500mg', 'Antibiótico', TRUE, 45.90),
('7894561237890', 'EMS', 'Dorflex', 'Relaxante Muscular', FALSE, 12.00),
('7891122334455', 'Medley', 'Dipirona 1g', 'Analgésico', FALSE, 8.75),
('7896677889900', 'Roche', 'Rivotril 2mg', 'Ansiolítico', TRUE, 60.00),
('7891000000001', 'Neoquimica', 'Paracetamol 750mg', 'Genérico', FALSE, 9.90),
('7891000000002', 'Eurofarma', 'Ibuprofeno 600mg', 'Genérico', FALSE, 14.50),
('7891000000003', 'Takeda', 'Neosaldina', 'Referência', FALSE, 25.00),
('7891000000004', 'Ache', 'Sibutramina 15mg', 'Referência', TRUE, 85.00),
('7891000000005', 'Medley', 'Loratadina 10mg', 'Genérico', FALSE, 18.00),
('7891000000006', 'AstraZeneca', 'Omeprazol 20mg', 'Genérico', FALSE, 11.50);

-- Inserção de dados na tabela Estoque
INSERT INTO Estoque (cod_barras, lote, quantidade_disponivel, data_validade, localizacao) VALUES
('7891234567890', 'LOTE001', 100, '2027-12-31', 'Prateleira A1'),
('7891234567890', 'LOTE002', 50, '2028-06-30', 'Prateleira A1'),
('7890987654321', 'LOTEABC', 30, '2027-10-15', 'Refrigerador B2'),
('7894561237890', 'LOTEXYZ', 200, '2028-01-01', 'Prateleira C3'),
('7891122334455', 'LOTE123', 150, '2027-09-20', 'Prateleira A2'),
('7896677889900', 'LOTE456', 20, '2027-11-05', 'Armário Seg. D1'),
('7890987654321', 'LOTEAMX2', 25, '2028-12-10', 'Refrigerador B2'),
('7896677889900', 'LOTERIV2', 15, '2028-11-30', 'Armário Seg. D1'),
('7891000000001', 'LOTEPAR1', 300, '2028-05-10', 'Prateleira B1'),
('7891000000001', 'LOTEPAR2', 120, '2029-01-15', 'Prateleira B1'),
('7891000000002', 'LOTEIBU1', 120, '2028-08-15', 'Prateleira B1'),
('7891000000003', 'LOTENEO1', 90, '2028-09-20', 'Prateleira C2'),
('7891000000004', 'LOTESIB1', 40, '2028-01-20', 'Armário Seg. D2'),
('7891000000004', 'LOTESIB2', 30, '2029-03-10', 'Armário Seg. D2'),
('7891000000005', 'LOTELOR1', 85, '2028-11-30', 'Prateleira C1'),
('7891000000006', 'LOTEOME1', 150, '2029-02-28', 'Prateleira B3');

-- Inserção de dados na tabela Venda
INSERT INTO Venda (id_venda, data, cpf_cliente, cpf_funcionario, valor_total, status, finalizada_em, cancelada_em) VALUES
(1, '2026-04-04 10:30:00', '111.222.333-44', '123.456.789-00', 27.50, 'finalizada', '2026-04-04 10:35:00', NULL),
(2, '2026-04-04 11:00:00', '555.666.777-88', '987.654.321-00', 45.90, 'finalizada', '2026-04-04 11:02:00', NULL),
(3, '2026-04-04 14:15:00', NULL, '123.456.789-00', 12.00, 'finalizada', '2026-04-04 14:16:00', NULL),
(4, '2026-04-05 09:00:00', '101.101.101-11', '222.000.222-00', 9.90, 'finalizada', '2026-04-05 09:03:00', NULL),
(5, '2026-04-05 10:20:00', '303.303.303-33', '987.654.321-00', 85.00, 'finalizada', '2026-04-05 10:25:00', NULL),
(6, '2026-04-05 11:45:00', '202.202.202-22', '123.456.789-00', 18.00, 'finalizada', '2026-04-05 11:47:00', NULL),
(7, '2026-04-06 08:30:00', '404.404.404-44', '222.000.222-00', 14.50, 'aberta', NULL, NULL),
(8, '2026-04-06 13:10:00', '606.606.606-66', '987.654.321-00', 60.00, 'finalizada', '2026-04-06 13:15:00', NULL),
(9, '2026-04-07 15:22:00', '505.505.505-55', '123.456.789-00', 25.00, 'cancelada', NULL, '2026-04-07 15:23:00'),
(10, '2026-04-08 17:00:00', '707.707.707-77', '222.000.222-00', 45.90, 'finalizada', '2026-04-08 17:05:00', NULL),
(11, '2026-04-08 18:10:00', '808.808.808-88', '111.000.111-00', 29.00, 'finalizada', '2026-04-08 18:12:00', NULL),
(12, '2026-04-09 10:00:00', '111.222.333-44', '444.000.444-00', 30.00, 'finalizada', '2026-04-09 10:05:00', NULL),
(13, '2026-04-09 11:00:00', '111.222.333-44', '123.456.789-00', 60.00, 'finalizada', '2026-04-09 11:03:00', NULL),
(14, '2026-04-09 11:30:00', '101.101.101-11', '222.000.222-00', 85.00, 'finalizada', '2026-04-09 11:34:00', NULL),
(15, '2026-04-09 12:00:00', '202.202.202-22', '987.654.321-00', 60.00, 'finalizada', '2026-04-09 12:04:00', NULL),
(16, '2026-04-09 13:00:00', '404.404.404-44', '222.000.222-00', 45.90, 'finalizada', '2026-04-09 13:05:00', NULL),
(17, '2026-04-09 14:00:00', '505.505.505-55', '123.456.789-00', 85.00, 'finalizada', '2026-04-09 14:05:00', NULL),
(18, '2026-04-09 15:00:00', '999.888.777-66', '987.654.321-00', 60.00, 'finalizada', '2026-04-09 15:06:00', NULL);

-- Inserção de dados na tabela Receita
INSERT INTO Receita (id_receita, data, crm_medico, cpf_cliente) VALUES
(1, '2026-04-03', 'CRM/SP 123456', '555.666.777-88'),
(2, '2026-04-02', 'CRM/RJ 789012', '111.222.333-44'),
(3, '2026-04-04', 'CRM/SP 111222', '303.303.303-33'),
(4, '2026-04-05', 'CRM/MG 555666', '606.606.606-66'),
(5, '2026-04-06', 'CRM/SP 777888', '707.707.707-77'),
(6, '2026-04-07', 'CRM/SP 123456', '101.101.101-11'),
(7, '2026-04-07', 'CRM/RJ 345678', '202.202.202-22'),
(8, '2026-04-08', 'CRM/PR 999000', '404.404.404-44'),
(9, '2026-04-08', 'CRM/SP 234567', '505.505.505-55'),
(10, '2026-04-08', 'CRM/SP 456789', '999.888.777-66'),
(11, '2026-04-09', 'CRM/SP 333444', '808.808.808-88');

-- Inserção de dados na tabela Item_Receita
INSERT INTO Item_Receita (id_receita, cod_barras, quantidade, observacoes) VALUES
(1, '7890987654321', 1, 'Tomar 1 comprimido a cada 8 horas por 7 dias.'),
(2, '7896677889900', 1, 'Tomar 1 comprimido antes de dormir.'),
(3, '7891000000004', 1, 'Uso contínuo.'),
(4, '7896677889900', 1, 'Em caso de crise.'),
(5, '7890987654321', 2, 'Tratamento por 14 dias.'),
(6, '7891000000004', 1, 'Avaliação em 30 dias.'),
(7, '7896677889900', 1, 'Uso noturno.'),
(8, '7890987654321', 1, 'Uso supervisionado.'),
(9, '7891000000004', 1, 'Acompanhamento nutricional.'),
(10, '7896677889900', 1, 'Uso SOS.'),
(11, '7890987654321', 1, 'Uso por 7 dias.');

-- Inserção de dados na tabela Item_Venda
INSERT INTO Item_Venda (id_venda, cod_barras, lote, quantidade, preco_venda, id_receita) VALUES
(1, '7891234567890', 'LOTE001', 1, 15.50, NULL),
(1, '7894561237890', 'LOTEXYZ', 1, 12.00, NULL),
(2, '7890987654321', 'LOTEABC', 1, 45.90, 1),
(3, '7894561237890', 'LOTEXYZ', 1, 12.00, NULL),
(4, '7891000000001', 'LOTEPAR1', 1, 9.90, NULL),
(5, '7891000000004', 'LOTESIB1', 1, 85.00, 3),
(6, '7891000000005', 'LOTELOR1', 1, 18.00, NULL),
(7, '7891000000002', 'LOTEIBU1', 1, 14.50, NULL),
(8, '7896677889900', 'LOTE456', 1, 60.00, 4),
(9, '7891000000003', 'LOTENEO1', 1, 25.00, NULL),
(10, '7890987654321', 'LOTEABC', 1, 45.90, 5),
(11, '7891000000002', 'LOTEIBU1', 2, 14.50, NULL),
(12, '7891234567890', 'LOTE001', 1, 15.50, NULL),
(12, '7891000000002', 'LOTEIBU1', 1, 14.50, NULL),
(13, '7896677889900', 'LOTE456', 1, 60.00, 2),
(14, '7891000000004', 'LOTESIB1', 1, 85.00, 6),
(15, '7896677889900', 'LOTE456', 1, 60.00, 7),
(16, '7890987654321', 'LOTEABC', 1, 45.90, 8),
(17, '7891000000004', 'LOTESIB1', 1, 85.00, 9),
(18, '7896677889900', 'LOTE456', 1, 60.00, 10);

INSERT INTO Uso_Receita (id_receita, id_venda) VALUES
(1, 2),
(2, 13),
(3, 5),
(4, 8),
(5, 10),
(6, 14),
(7, 15),
(8, 16),
(9, 17),
(10, 18);

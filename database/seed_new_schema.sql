USE farmacia_db;

-- Seed unificado para schema novo
-- 1) Dados base do novo modelo
-- 2) Complementos do seed legado adaptados
-- 3) Carga Golgi convertida para Produto/Estoque

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

-- Complemento do seed legado adaptado para schema novo
INSERT IGNORE INTO Funcionario (cpf, nome, cargo, registro_profissional, ativo) VALUES
('11111111111', 'Maria Silva', 'farmaceutico', 'CRF12345', 1),
('22222222222', 'Joao Costa', 'atendente', NULL, 1);

INSERT IGNORE INTO Cliente (cpf, nome, data_nascimento, telefone) VALUES
('33333333333', 'Ana Pereira', '1991-05-10', '11999999999'),
('44444444444', 'Carlos Souza', '1987-08-17', '11888888888');

INSERT IGNORE INTO Produto (cod_barras, marca, nome, tipo, precisa_receita, preco, ativo) VALUES
('789100000001', 'Farmaco SA', 'Dipirona 500mg', 'generico', 0, 7.90, 1),
('789100000002', 'Laboratorio Beta', 'Amoxicilina 500mg', 'referencia', 1, 32.50, 1);

INSERT IGNORE INTO Estoque (cod_barras, lote, quantidade_disponivel, data_validade, localizacao) VALUES
('789100000001', 'DIP-001', 100, '2027-12-31', 'Prateleira A1'),
('789100000001', 'DIP-002', 50, '2026-11-30', 'Prateleira A2'),
('789100000002', 'AMX-010', 40, '2026-06-30', 'Prateleira B1');

INSERT INTO Medico (crm, nome) VALUES
('CRM123456', 'Dra Paula Mendes')
ON DUPLICATE KEY UPDATE nome = VALUES(nome);

INSERT INTO Receita (data, crm_medico, cpf_cliente)
SELECT CURDATE(), 'CRM123456', '33333333333'
WHERE NOT EXISTS (
    SELECT 1
    FROM Receita
    WHERE crm_medico = 'CRM123456' AND cpf_cliente = '33333333333'
);

INSERT IGNORE INTO Item_Receita (id_receita, cod_barras, quantidade, observacoes)
SELECT r.id_receita, '789100000002', 1, '1 capsula a cada 8 horas'
FROM Receita r
WHERE r.crm_medico = 'CRM123456' AND r.cpf_cliente = '33333333333'
ORDER BY r.id_receita DESC
LIMIT 1;

-- Importacao de medicamentos do golgi_bot_GUI_app (id -> cod_barras)
-- Script idempotente para schema novo

INSERT INTO Produto (cod_barras, marca, nome, tipo, precisa_receita, preco, ativo) VALUES
('17292','HU','ACETAZOLAMIDA 250 MG 1 CP','generico',0,91.69,1),
('17293','HU','ACICLOVIR 200 MG 1 CP','generico',0,85.58,1),
('17296','HU','ACIDO ACETILSALICILICO 100 MG 3 CP','generico',0,69.43,1),
('17297','HU','ACIDO ASCORBICO 500 MG 1 CP','generico',0,45.32,1),
('17298','HU','ACIDO FOLICO 5 MG 1 CP','generico',0,45.44,1),
('21393','HU','ALBENDAZOL 400 MG 1 CP','generico',0,90.18,1),
('17301','HU','ALOPURINOL 100 MG 1 CP','generico',0,32.97,1),
('17302','HU','AMIODARONA 200 MG 1 CP','generico',0,86.09,1),
('17303','HU','AMOXICILINA 500 MG 1 CP','generico',0,55.52,1),
('17304','HU','AMPICILINA 500 MG 1 CP','generico',0,53.35,1),
('17306','HU','ANLODIPINO 10 MG 1 CP','generico',0,21.21,1),
('17307','HU','ANLODIPINO 5 MG 1 CP','generico',0,24.02,1),
('17308','HU','ATENOLOL 25 MG 1 CP','generico',0,73.85,1),
('17309','HU','ATENOLOL 50 MG 1 CP','generico',0,62.83,1),
('17310','HU','ATENOLOL 50 MG 2 CP','generico',0,53.10,1),
('20336','HU','ATORVASTATINA 20 MG 1 CP','generico',0,77.41,1),
('20479','HU','ATORVASTATINA 20 MG 2 CP','generico',0,24.93,1),
('18762','HU','AZITROMICINA 500 MG 1 CP','generico',0,69.40,1),
('19675','HU','AZITROMICINA 500 MG 3 CP','generico',0,48.49,1),
('17311','HU','BACLOFENO 10 MG 1 CP','generico',0,44.04,1),
('17312','HU','BISACODIL 5 MG 1 CP','generico',0,54.83,1),
('26482','HU','BUDESONIDA 200 MCG 1 CAPS INALATORIA','generico',0,24.92,1),
('27147','HU','BUDESONIDA 200 MCG 2 CAPS INALATORIA','generico',0,74.39,1),
('17315','HU','CABERGOLINA 0,5 MG 2 CP','generico',0,20.65,1),
('17317','HU','CAPTOPRIL 12,5 MG 0,5 CP','generico',0,47.23,1),
('17316','HU','CAPTOPRIL 12,5 MG 1 CP','generico',0,91.38,1),
('29111','HU','CAPTOPRIL 25MG 0,5 CP','generico',0,15.04,1),
('29112','HU','CAPTOPRIL 25 MG 1/4 CP','generico',0,49.33,1),
('17318','HU','CAPTOPRIL 25 MG 1 CP','generico',0,45.28,1),
('17319','HU','CAPTOPRIL 25 MG 2 CP','generico',0,88.52,1),
('17469','HU','CARBONATO DE CALCIO CAPSULA 500 MG 1 CP','generico',0,48.61,1),
('17321','HU','CARVEDILOL 12,5 MG 1 CP','generico',0,34.69,1),
('17322','HU','CARVEDILOL 3,125 MG 1 CP','generico',0,39.48,1),
('17323','HU','CARVEDILOL 3,125 MG 2 CP','generico',0,27.56,1),
('17324','HU','CEFALEXINA 500 MG 1 CP','generico',0,33.80,1),
('17325','HU','CETOROLACO 10 MG 1 CP','generico',0,8.26,1),
('19759','HU','CIPROFIBRATO 100 MG 1 CP','generico',0,23.58,1),
('17326','HU','CIPROFLOXACINO 250 MG 1 CP','generico',0,51.36,1),
('17327','HU','CIPROFLOXACINO 250 MG 2 CP','generico',0,17.94,1),
('26023','HU','CIPROFLOXACINO 500 MG 1 CP','generico',0,70.52,1),
('17329','HU','CLARITROMICINA 500 MG 0,5 CP','generico',0,45.01,1),
('17328','HU','CLARITROMICINA 500 MG 1 CP','generico',0,68.60,1),
('17330','HU','CLINDAMICINA 300 MG 1 CP','generico',0,77.47,1),
('17331','HU','CLINDAMICINA 300 MG 2 CP','generico',0,33.23,1),
('26886','HU','CLONIDINA 0,10 MG 1CP','generico',0,79.11,1),
('17335','HU','CLOPIDOGREL 75 MG 1 CP','generico',0,22.16,1),
('17337','HU','CLORETO DE POTASSIO 600 MG 1 CP','generico',0,81.35,1),
('17338','HU','CLORPROPAMIDA 250 MG 1 CP','generico',0,22.60,1),
('17339','HU','CLORTALIDONA 25 MG 1 CP','generico',0,39.45,1),
('17342','HU','COLCHICINA 0,5 MG 1 CP','generico',0,77.13,1),
('17343','HU','COMPLEXO B 1 CP','generico',0,72.60,1),
('17344','HU','DESLORATADINA 5 MG 1 CP','generico',0,41.58,1),
('28634','HU','DEXAMETASONA 4MG 0,5 CP','generico',0,76.15,1),
('17345','HU','DEXAMETASONA 4 MG 1 CP','generico',0,34.37,1),
('17349','HU','DICLOFENACO SODICO 50 MG 1 CP','generico',0,23.74,1),
('17351','HU','DIGOXINA 0,25 MG 1 CP','generico',0,43.38,1),
('17352','HU','DILTIAZEM 30 MG 1 CP','generico',0,41.86,1),
('17353','HU','DILTIAZEM 30 MG 2 CP','generico',0,25.81,1),
('17354','HU','DIMETICONA 40 MG 1 CP','generico',0,36.91,1),
('17355','HU','DIPIRONA +ADIFENINA+PROMETAZINA (500+10+5) MG 1 CP','generico',0,40.61,1),
('17356','HU','DIPIRONA SODICA 500 MG 1 CP','generico',0,14.37,1),
('17357','HU','DIPIRONA SODICA 500 MG 2 CP','generico',0,18.85,1),
('19804','HU','DOMPERIDONA 10MG 1CP','generico',0,29.32,1),
('17358','HU','DOXAZOSINA 2 MG 1 CP','generico',0,9.81,1),
('27985','HU','DOXICICLINA 100 MG 1CP','generico',0,42.45,1),
('17359','HU','ENALAPRIL 5 MG 1 CP','generico',0,22.34,1),
('17360','HU','ENALAPRIL 5 MG 2 CP','generico',0,88.66,1),
('17361','HU','ENALAPRIL 5 MG 4 CP','generico',0,50.70,1),
('20395','HU','ERGOMETRINA 0,2 MG 1 CP','generico',0,34.61,1),
('17363','HU','ESCOPOLAMINA 10 MG 1 CP','generico',0,55.54,1),
('17362','HU','ESCOPOLAMINA +DIPIRONA DRAGEA (10+250) MG 1 CP','generico',0,94.16,1),
('28638','HU','ESOMEPRAZOL MAGNESICO 20 MG 1 CP','generico',0,31.56,1),
('17364','HU','ESPIRONOLACTONA 100 MG 1 CP','generico',0,56.30,1),
('17365','HU','ESPIRONOLACTONA 25 MG 1 CP','generico',0,68.09,1),
('17366','HU','ESPIRONOLACTONA 25 MG 2 CP','generico',0,31.01,1),
('17369','HU','FLUCONAZOL 100 MG 1 CP','generico',0,93.58,1),
('20473','HU','FLUCONAZOL 150 MG 1 CP','generico',0,45.01,1),
('17372','HU','FOLINATO CALCICO 15 MG 1 CP','generico',0,55.07,1),
('26548','HU','FOLINATO CALCICO MANIPULADO 15 MG 1 CP','generico',0,36.49,1),
('26483','HU','FORMOTEROL 12 MCG 1 CAPS INALATORIA','generico',0,74.19,1),
('17374','HU','FUROSEMIDA 40 MG 0,5 CP','generico',0,24.50,1),
('17373','HU','FUROSEMIDA 40 MG 1 CP','generico',0,89.60,1),
('17375','HU','FUROSEMIDA 40 MG 2 CP','generico',0,68.37,1),
('17378','HU','GLIBENCLAMIDA 5 MG 0,5 CP','generico',0,71.75,1),
('17377','HU','GLIBENCLAMIDA 5 MG 1 CP','generico',0,25.39,1),
('17380','HU','HIDRALAZINA 25 MG 1 CP','generico',0,12.69,1),
('17381','HU','HIDRALAZINA 25 MG 2 CP','generico',0,16.29,1),
('17382','HU','HIDRALAZINA 25 MG 4 CP','generico',0,54.02,1),
('19946','HU','HIDRALAZINA 50MG 1 CP','generico',0,92.78,1),
('19990','HU','HIDRALAZINA 50 MG 2 CP','generico',0,54.76,1),
('17383','HU','HIDROCLOROTIAZIDA 25 MG 1 CP','generico',0,84.97,1),
('18534','HU','HIDROCLOROTIAZIDA 25 MG 2 CP','generico',0,11.09,1),
('17384','HU','HIDROCLOROTIAZIDA 50 MG 1 CP','generico',0,85.18,1),
('17385','HU','HIDROXICLOROQUINA 400 MG 1 CP','generico',0,13.28,1),
('18944','HU','HIDROXIZINA 25 MG 1 CP','generico',0,24.23,1),
('17387','HU','ISOSSORBIDA DINITRATO 5 MG 1 CP','generico',0,23.94,1),
('17389','HU','ISOSSORBIDA MONONITRATO 20 MG 0,5 CP','generico',0,56.23,1),
('17388','HU','ISOSSORBIDA MONONITRATO 20 MG 1 CP','generico',0,31.77,1),
('17390','HU','ISOSSORBIDA MONONITRATO 20 MG 2 CP','generico',0,89.14,1),
('17391','HU','IVERMECTINA 6 MG 1 CP','generico',0,46.10,1),
('17392','HU','LEVODOPA +BENSERAZIDA (200+50) MG 1 CP','generico',0,39.92,1),
('17393','HU','LEVODOPA +BENZERAZIDA (200+50) MG 0,5 CP','generico',0,36.20,1),
('17466','HU','LEVOFLOXACINO 500 MG 1 CP','generico',0,52.31,1),
('17395','HU','LOPERAMIDA 2 MG 1 CP','generico',0,41.04,1),
('17397','HU','LOSARTANA POTASSICA 50 MG 1 CP','generico',0,17.74,1),
('17399','HU','MESALAZINA 400 MG 1 CP','generico',0,27.03,1),
('28031','HU','MESALAZINA 800 MG 1 CP','generico',0,57.44,1),
('17402','HU','METFORMINA 850 MG 0,5 CP','generico',0,24.97,1),
('17401','HU','METFORMINA 850 MG 1 CP','generico',0,9.23,1),
('17403','HU','METILDOPA 250 MG 1 CP','generico',0,45.79,1),
('17404','HU','METILDOPA 250 MG 2 CP','generico',0,20.19,1),
('19705','HU','METIMAZOL 5MG 1CP','generico',0,58.16,1),
('28192','HU','METOCLOPRAMIDA 10MG 0,5 CP','generico',0,90.34,1),
('17406','HU','METOCLOPRAMIDA 10 MG 1 CP','generico',0,23.20,1),
('17407','HU','METOPROLOL 100 MG 1 CP','generico',0,56.50,1),
('17408','HU','METRONIDAZOL 250 MG 1 CP','generico',0,79.35,1),
('17409','HU','METRONIDAZOL 250 MG 2 CP','generico',0,35.68,1),
('17414','HU','NAPROXENO 275 MG 1 CP','generico',0,66.55,1),
('17468','HU','NEOMICINA 500 MG 1 CP','generico',0,34.11,1),
('27198','HU','NIMODIPINO 30MG/CP 1CP','generico',0,43.13,1),
('17415','HU','NITROFURANTOINA 100 MG 1 CP','generico',0,29.84,1),
('17416','HU','NORFLOXACINO 400 MG 1 CP','generico',0,22.82,1),
('17418','HU','OMEPRAZOL 20 MG 1 CP','generico',0,28.87,1),
('17419','HU','OMEPRAZOL 20 MG 2 CP','generico',0,57.40,1),
('19679','HU','OMEPRAZOL MAGNESICO 20 MG 1 CP','generico',0,67.56,1),
('19520','HU','ONDANSETRONA 4 MG 0,5 CP','generico',0,24.74,1),
('18899','HU','ONDANSETRONA 4 MG 1 CP','generico',0,80.22,1),
('18900','HU','ONDANSETRONA 4 MG 2 CP','generico',0,13.97,1),
('17424','HU','PARACETAMOL 500 MG 1 CP','generico',0,62.73,1),
('17425','HU','PARACETAMOL 750 MG 1 CP','generico',0,55.53,1),
('17426','HU','PENTOXIFILINA 400 MG 1 CP','generico',0,72.85,1),
('17427','HU','PERMANGANATO DE POTASSIO 100 MG 1 CP','generico',0,74.72,1),
('17923','HU','PIRIDOXINA 50 MG 1 CP','generico',0,81.47,1),
('17431','HU','PIRIMETAMINA 25 MG 1 CP','generico',0,17.07,1),
('17432','HU','PREDNISONA 20 MG 1 CP','generico',0,84.59,1),
('17433','HU','PREDNISONA 20 MG 2 CP','generico',0,78.66,1),
('17434','HU','PREDNISONA 5 MG 1 CP','generico',0,78.07,1),
('17435','HU','PREDNISONA 5 MG 2 CP','generico',0,23.24,1),
('17436','HU','PROGESTERONA NATURAL MICRONIZADA 200 MG 1 CP','generico',0,43.78,1),
('17438','HU','PROPILTIOURACILA 100 MG 1 CP','generico',0,76.55,1),
('17439','HU','PROPRANOLOL 10 MG 1 CP','generico',0,40.21,1),
('17440','HU','PROPRANOLOL 10 MG 2 CP','generico',0,51.41,1),
('17441','HU','PROPRANOLOL 40 MG 1 CP','generico',0,12.97,1),
('17442','HU','PROPRANOLOL 40 MG 2 CP','generico',0,7.98,1),
('17445','HU','SENE +TAMARINDO+ALCACUZ (400+19,5+19,5+9+4) MG 1 CP','generico',0,45.66,1),
('17447','HU','SINVASTATINA 10 MG 1 CP','generico',0,25.99,1),
('17448','HU','SINVASTATINA 20 MG 1 CP','generico',0,93.50,1),
('17449','HU','SINVASTATINA 20 MG 2 CP','generico',0,54.84,1),
('17450','HU','SULFADIAZINA 500 MG 1 CP','generico',0,83.42,1),
('17451','HU','SULFAMETOXAZOL + TRIMETOPRIMA (400+80) MG 1 CP','generico',0,67.98,1),
('17452','HU','SULFAMETOXAZOL + TRIMETOPRIMA (400+80) MG 2 CP','generico',0,57.03,1),
('17454','HU','SULFATO FERROSO 300 MG 1 CP','generico',0,16.15,1),
('17456','HU','TIAMINA 300 MG 1 CP','generico',0,11.67,1),
('17457','HU','TIROXINA SODICA 25 MCG 1 CP','generico',0,24.04,1),
('17458','HU','TIROXINA SODICA 50 MCG 1 CP','generico',0,45.51,1),
('17461','HU','VARFARINA 2,5 MG 1 CP','generico',0,77.91,1),
('17462','HU','VARFARINA 5 MG 1 CP','generico',0,49.22,1),
('17294','HU','ACIDO ACETILSALICILICO 100 MG 1 CP','generico',0,38.38,1),
('17295','HU','ACIDO ACETILSALICILICO 100 MG 2 CP','generico',0,32.01,1)
ON DUPLICATE KEY UPDATE
nome = VALUES(nome),
marca = VALUES(marca),
tipo = VALUES(tipo),
precisa_receita = VALUES(precisa_receita),
preco = VALUES(preco),
ativo = 1;

INSERT INTO Estoque (cod_barras, lote, quantidade_disponivel, data_validade, localizacao) VALUES
('17292','17292',20,'2029-12-31','Importado Golgi'),
('17293','17293',10,'2029-12-31','Importado Golgi'),
('17296','17296',10,'2029-12-31','Importado Golgi'),
('17297','17297',10,'2029-12-31','Importado Golgi'),
('17298','17298',10,'2029-12-31','Importado Golgi'),
('21393','21393',10,'2029-12-31','Importado Golgi'),
('17301','17301',10,'2029-12-31','Importado Golgi'),
('17302','17302',10,'2029-12-31','Importado Golgi'),
('17303','17303',10,'2029-12-31','Importado Golgi'),
('17304','17304',10,'2029-12-31','Importado Golgi'),
('17306','17306',10,'2029-12-31','Importado Golgi'),
('17307','17307',10,'2029-12-31','Importado Golgi'),
('17308','17308',10,'2029-12-31','Importado Golgi'),
('17309','17309',10,'2029-12-31','Importado Golgi'),
('17310','17310',10,'2029-12-31','Importado Golgi'),
('20336','20336',10,'2029-12-31','Importado Golgi'),
('20479','20479',10,'2029-12-31','Importado Golgi'),
('18762','18762',10,'2029-12-31','Importado Golgi'),
('19675','19675',10,'2029-12-31','Importado Golgi'),
('17311','17311',10,'2029-12-31','Importado Golgi'),
('17312','17312',10,'2029-12-31','Importado Golgi'),
('26482','26482',10,'2029-12-31','Importado Golgi'),
('27147','27147',10,'2029-12-31','Importado Golgi'),
('17315','17315',10,'2029-12-31','Importado Golgi'),
('17317','17317',10,'2029-12-31','Importado Golgi'),
('17316','17316',10,'2029-12-31','Importado Golgi'),
('29111','29111',10,'2029-12-31','Importado Golgi'),
('29112','29112',10,'2029-12-31','Importado Golgi'),
('17318','17318',10,'2029-12-31','Importado Golgi'),
('17319','17319',10,'2029-12-31','Importado Golgi'),
('17469','17469',10,'2029-12-31','Importado Golgi'),
('17321','17321',10,'2029-12-31','Importado Golgi'),
('17322','17322',10,'2029-12-31','Importado Golgi'),
('17323','17323',10,'2029-12-31','Importado Golgi'),
('17324','17324',10,'2029-12-31','Importado Golgi'),
('17325','17325',10,'2029-12-31','Importado Golgi'),
('19759','19759',10,'2029-12-31','Importado Golgi'),
('17326','17326',10,'2029-12-31','Importado Golgi'),
('17327','17327',10,'2029-12-31','Importado Golgi'),
('26023','26023',10,'2029-12-31','Importado Golgi'),
('17329','17329',10,'2029-12-31','Importado Golgi'),
('17328','17328',10,'2029-12-31','Importado Golgi'),
('17330','17330',10,'2029-12-31','Importado Golgi'),
('17331','17331',10,'2029-12-31','Importado Golgi'),
('26886','26886',10,'2029-12-31','Importado Golgi'),
('17335','17335',10,'2029-12-31','Importado Golgi'),
('17337','17337',10,'2029-12-31','Importado Golgi'),
('17338','17338',10,'2029-12-31','Importado Golgi'),
('17339','17339',10,'2029-12-31','Importado Golgi'),
('17342','17342',10,'2029-12-31','Importado Golgi'),
('17343','17343',10,'2029-12-31','Importado Golgi'),
('17344','17344',10,'2029-12-31','Importado Golgi'),
('28634','28634',10,'2029-12-31','Importado Golgi'),
('17345','17345',10,'2029-12-31','Importado Golgi'),
('17349','17349',10,'2029-12-31','Importado Golgi'),
('17351','17351',10,'2029-12-31','Importado Golgi'),
('17352','17352',10,'2029-12-31','Importado Golgi'),
('17353','17353',10,'2029-12-31','Importado Golgi'),
('17354','17354',10,'2029-12-31','Importado Golgi'),
('17355','17355',7,'2029-12-31','Importado Golgi'),
('17356','17356',10,'2029-12-31','Importado Golgi'),
('17357','17357',10,'2029-12-31','Importado Golgi'),
('19804','19804',10,'2029-12-31','Importado Golgi'),
('17358','17358',10,'2029-12-31','Importado Golgi'),
('27985','27985',10,'2029-12-31','Importado Golgi'),
('17359','17359',10,'2029-12-31','Importado Golgi'),
('17360','17360',10,'2029-12-31','Importado Golgi'),
('17361','17361',10,'2029-12-31','Importado Golgi'),
('20395','20395',10,'2029-12-31','Importado Golgi'),
('17363','17363',10,'2029-12-31','Importado Golgi'),
('17362','17362',10,'2029-12-31','Importado Golgi'),
('28638','28638',10,'2029-12-31','Importado Golgi'),
('17364','17364',10,'2029-12-31','Importado Golgi'),
('17365','17365',10,'2029-12-31','Importado Golgi'),
('17366','17366',10,'2029-12-31','Importado Golgi'),
('17369','17369',10,'2029-12-31','Importado Golgi'),
('20473','20473',10,'2029-12-31','Importado Golgi'),
('17372','17372',10,'2029-12-31','Importado Golgi'),
('26548','26548',10,'2029-12-31','Importado Golgi'),
('26483','26483',10,'2029-12-31','Importado Golgi'),
('17374','17374',10,'2029-12-31','Importado Golgi'),
('17373','17373',10,'2029-12-31','Importado Golgi'),
('17375','17375',10,'2029-12-31','Importado Golgi'),
('17378','17378',10,'2029-12-31','Importado Golgi'),
('17377','17377',10,'2029-12-31','Importado Golgi'),
('17380','17380',10,'2029-12-31','Importado Golgi'),
('17381','17381',10,'2029-12-31','Importado Golgi'),
('17382','17382',10,'2029-12-31','Importado Golgi'),
('19946','19946',10,'2029-12-31','Importado Golgi'),
('19990','19990',10,'2029-12-31','Importado Golgi'),
('17383','17383',10,'2029-12-31','Importado Golgi'),
('18534','18534',10,'2029-12-31','Importado Golgi'),
('17384','17384',10,'2029-12-31','Importado Golgi'),
('17385','17385',10,'2029-12-31','Importado Golgi'),
('18944','18944',10,'2029-12-31','Importado Golgi'),
('17387','17387',10,'2029-12-31','Importado Golgi'),
('17389','17389',10,'2029-12-31','Importado Golgi'),
('17388','17388',10,'2029-12-31','Importado Golgi'),
('17390','17390',10,'2029-12-31','Importado Golgi'),
('17391','17391',10,'2029-12-31','Importado Golgi'),
('17392','17392',10,'2029-12-31','Importado Golgi'),
('17393','17393',10,'2029-12-31','Importado Golgi'),
('17466','17466',10,'2029-12-31','Importado Golgi'),
('17395','17395',10,'2029-12-31','Importado Golgi'),
('17397','17397',10,'2029-12-31','Importado Golgi'),
('17399','17399',10,'2029-12-31','Importado Golgi'),
('28031','28031',10,'2029-12-31','Importado Golgi'),
('17402','17402',10,'2029-12-31','Importado Golgi'),
('17401','17401',10,'2029-12-31','Importado Golgi'),
('17403','17403',10,'2029-12-31','Importado Golgi'),
('17404','17404',10,'2029-12-31','Importado Golgi'),
('19705','19705',10,'2029-12-31','Importado Golgi'),
('28192','28192',10,'2029-12-31','Importado Golgi'),
('17406','17406',10,'2029-12-31','Importado Golgi'),
('17407','17407',10,'2029-12-31','Importado Golgi'),
('17408','17408',10,'2029-12-31','Importado Golgi'),
('17409','17409',10,'2029-12-31','Importado Golgi'),
('17414','17414',10,'2029-12-31','Importado Golgi'),
('17468','17468',10,'2029-12-31','Importado Golgi'),
('27198','27198',10,'2029-12-31','Importado Golgi'),
('17415','17415',10,'2029-12-31','Importado Golgi'),
('17416','17416',10,'2029-12-31','Importado Golgi'),
('17418','17418',10,'2029-12-31','Importado Golgi'),
('17419','17419',10,'2029-12-31','Importado Golgi'),
('19679','19679',10,'2029-12-31','Importado Golgi'),
('19520','19520',10,'2029-12-31','Importado Golgi'),
('18899','18899',10,'2029-12-31','Importado Golgi'),
('18900','18900',10,'2029-12-31','Importado Golgi'),
('17424','17424',10,'2029-12-31','Importado Golgi'),
('17425','17425',10,'2029-12-31','Importado Golgi'),
('17426','17426',10,'2029-12-31','Importado Golgi'),
('17427','17427',10,'2029-12-31','Importado Golgi'),
('17923','17923',10,'2029-12-31','Importado Golgi'),
('17431','17431',10,'2029-12-31','Importado Golgi'),
('17432','17432',10,'2029-12-31','Importado Golgi'),
('17433','17433',10,'2029-12-31','Importado Golgi'),
('17434','17434',10,'2029-12-31','Importado Golgi'),
('17435','17435',10,'2029-12-31','Importado Golgi'),
('17436','17436',10,'2029-12-31','Importado Golgi'),
('17438','17438',10,'2029-12-31','Importado Golgi'),
('17439','17439',10,'2029-12-31','Importado Golgi'),
('17440','17440',10,'2029-12-31','Importado Golgi'),
('17441','17441',10,'2029-12-31','Importado Golgi'),
('17442','17442',10,'2029-12-31','Importado Golgi'),
('17445','17445',10,'2029-12-31','Importado Golgi'),
('17447','17447',10,'2029-12-31','Importado Golgi'),
('17448','17448',10,'2029-12-31','Importado Golgi'),
('17449','17449',10,'2029-12-31','Importado Golgi'),
('17450','17450',10,'2029-12-31','Importado Golgi'),
('17451','17451',10,'2029-12-31','Importado Golgi'),
('17452','17452',10,'2029-12-31','Importado Golgi'),
('17454','17454',10,'2029-12-31','Importado Golgi'),
('17456','17456',10,'2029-12-31','Importado Golgi'),
('17457','17457',10,'2029-12-31','Importado Golgi'),
('17458','17458',10,'2029-12-31','Importado Golgi'),
('17461','17461',10,'2029-12-31','Importado Golgi'),
('17462','17462',10,'2029-12-31','Importado Golgi'),
('17294','17294',10,'2029-12-31','Importado Golgi'),
('17295','17295',10,'2029-12-31','Importado Golgi')
ON DUPLICATE KEY UPDATE
quantidade_disponivel = VALUES(quantidade_disponivel),
data_validade = VALUES(data_validade),
localizacao = VALUES(localizacao);

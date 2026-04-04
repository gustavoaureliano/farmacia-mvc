CREATE DATABASE IF NOT EXISTS farmacia_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE farmacia_db;

-- Tabela Cliente
CREATE TABLE Cliente (
    cpf VARCHAR(14) PRIMARY KEY, -- Considerando CPF como string para incluir pontos e tracos, se necessario
    nome VARCHAR(255) NOT NULL,
    data_nascimento DATE,
    telefone VARCHAR(20)
) ENGINE=InnoDB;

-- Tabela Funcionario
CREATE TABLE Funcionario (
    cpf VARCHAR(14) PRIMARY KEY, -- Considerando CPF como string
    nome VARCHAR(255) NOT NULL,
    cargo VARCHAR(100),
    registro_profissional VARCHAR(50),
    ativo BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

-- Tabela Medico
CREATE TABLE Medico (
    crm VARCHAR(20) PRIMARY KEY, -- CRM como string
    nome VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

-- Tabela Produto
CREATE TABLE Produto (
    cod_barras VARCHAR(50) PRIMARY KEY,
    marca VARCHAR(100),
    nome VARCHAR(255) NOT NULL,
    tipo VARCHAR(100),
    precisa_receita BOOLEAN,
    preco DECIMAL(10, 2) NOT NULL,
    ativo BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

-- Tabela Estoque
CREATE TABLE Estoque (
    cod_barras VARCHAR(50),
    lote VARCHAR(50),
    quantidade_disponivel INT NOT NULL,
    data_validade DATE,
    localizacao VARCHAR(100),
    PRIMARY KEY (cod_barras, lote),
    FOREIGN KEY (cod_barras) REFERENCES Produto(cod_barras)
) ENGINE=InnoDB;

-- Tabela Venda
CREATE TABLE Venda (
    id_venda INT AUTO_INCREMENT PRIMARY KEY,
    data DATETIME NOT NULL,
    cpf_cliente VARCHAR(14),
    cpf_funcionario VARCHAR(14) NOT NULL,
    valor_total DECIMAL(10, 2) NOT NULL,
    status ENUM('aberta', 'finalizada', 'cancelada') NOT NULL DEFAULT 'aberta',
    finalizada_em DATETIME NULL,
    cancelada_em DATETIME NULL,
    FOREIGN KEY (cpf_cliente) REFERENCES Cliente(cpf),
    FOREIGN KEY (cpf_funcionario) REFERENCES Funcionario(cpf)
) ENGINE=InnoDB;

-- Tabela Receita
CREATE TABLE Receita (
    id_receita INT AUTO_INCREMENT PRIMARY KEY,
    data DATE NOT NULL,
    crm_medico VARCHAR(20) NOT NULL,
    cpf_cliente VARCHAR(14) NOT NULL,
    FOREIGN KEY (crm_medico) REFERENCES Medico(crm),
    FOREIGN KEY (cpf_cliente) REFERENCES Cliente(cpf)
) ENGINE=InnoDB;

-- Tabela Item_Receita
CREATE TABLE Item_Receita (
    id_receita INT,
    cod_barras VARCHAR(50),
    quantidade INT NOT NULL,
    observacoes TEXT,
    PRIMARY KEY (id_receita, cod_barras),
    FOREIGN KEY (id_receita) REFERENCES Receita(id_receita),
    FOREIGN KEY (cod_barras) REFERENCES Produto(cod_barras)
) ENGINE=InnoDB;

-- Tabela Item_Venda
CREATE TABLE Item_Venda (
    id_venda INT,
    cod_barras VARCHAR(50),
    lote VARCHAR(50),
    quantidade INT NOT NULL,
    preco_venda DECIMAL(10, 2) NOT NULL,
    id_receita INT NULL,
    PRIMARY KEY (id_venda, cod_barras, lote),
    FOREIGN KEY (id_venda) REFERENCES Venda(id_venda),
    FOREIGN KEY (cod_barras) REFERENCES Produto(cod_barras),
    FOREIGN KEY (cod_barras, lote) REFERENCES Estoque(cod_barras, lote),
    FOREIGN KEY (id_receita) REFERENCES Receita(id_receita)
) ENGINE=InnoDB;

-- Receita usada no maximo em uma venda finalizada
CREATE TABLE Uso_Receita (
    id_receita INT PRIMARY KEY,
    id_venda INT NOT NULL,
    utilizada_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_receita) REFERENCES Receita(id_receita),
    FOREIGN KEY (id_venda) REFERENCES Venda(id_venda)
) ENGINE=InnoDB;

-- Indices para consultas frequentes
CREATE INDEX idx_produto_ativo_nome ON Produto(ativo, nome);
CREATE INDEX idx_produto_ativo_tipo ON Produto(ativo, tipo);
CREATE INDEX idx_produto_marca ON Produto(marca);

CREATE INDEX idx_estoque_cod_validade ON Estoque(cod_barras, data_validade);
CREATE INDEX idx_estoque_validade ON Estoque(data_validade);

CREATE INDEX idx_venda_data ON Venda(data);
CREATE INDEX idx_venda_cliente_data ON Venda(cpf_cliente, data);
CREATE INDEX idx_venda_funcionario_data ON Venda(cpf_funcionario, data);
CREATE INDEX idx_venda_status_data ON Venda(status, data);

CREATE INDEX idx_receita_cliente_data ON Receita(cpf_cliente, data);
CREATE INDEX idx_receita_medico_data ON Receita(crm_medico, data);

CREATE INDEX idx_item_receita_cod ON Item_Receita(cod_barras, id_receita);

CREATE INDEX idx_item_venda_receita ON Item_Venda(id_receita);
CREATE INDEX idx_item_venda_prod_lote ON Item_Venda(cod_barras, lote);

CREATE INDEX idx_uso_receita_venda ON Uso_Receita(id_venda);

CREATE DATABASE IF NOT EXISTS farmacia_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE farmacia_db;

CREATE TABLE produtos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(150) NOT NULL,
  principio_ativo VARCHAR(150) NULL,
  marca_laboratorio VARCHAR(120) NULL,
  tipo ENUM('generico', 'similar', 'referencia') NOT NULL,
  exige_receita TINYINT(1) NOT NULL DEFAULT 0,
  preco_atual DECIMAL(10,2) NOT NULL,
  codigo_barras VARCHAR(50) NOT NULL UNIQUE,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE lotes_estoque (
  id INT AUTO_INCREMENT PRIMARY KEY,
  produto_id INT NOT NULL,
  numero_lote VARCHAR(60) NOT NULL,
  validade DATE NOT NULL,
  quantidade_disponivel INT NOT NULL,
  localizacao VARCHAR(80) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_lote_produto FOREIGN KEY (produto_id) REFERENCES produtos(id),
  CONSTRAINT uq_lote_produto UNIQUE (produto_id, numero_lote)
) ENGINE=InnoDB;

CREATE TABLE clientes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(150) NOT NULL,
  cpf CHAR(11) NOT NULL UNIQUE,
  data_nascimento DATE NULL,
  telefone VARCHAR(20) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE funcionarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(150) NOT NULL,
  cargo ENUM('farmaceutico', 'atendente') NOT NULL,
  cpf CHAR(11) NOT NULL UNIQUE,
  crf VARCHAR(30) NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE receitas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cliente_id INT NOT NULL,
  medico_nome VARCHAR(150) NOT NULL,
  crm VARCHAR(30) NOT NULL,
  data_receita DATE NOT NULL,
  observacoes TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_receita_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id)
) ENGINE=InnoDB;

CREATE TABLE receita_itens (
  id INT AUTO_INCREMENT PRIMARY KEY,
  receita_id INT NOT NULL,
  produto_id INT NOT NULL,
  posologia VARCHAR(255) NULL,
  CONSTRAINT fk_receita_item_receita FOREIGN KEY (receita_id) REFERENCES receitas(id),
  CONSTRAINT fk_receita_item_produto FOREIGN KEY (produto_id) REFERENCES produtos(id)
) ENGINE=InnoDB;

CREATE TABLE vendas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  data_venda DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  cliente_id INT NULL,
  funcionario_id INT NOT NULL,
  valor_total DECIMAL(10,2) NOT NULL DEFAULT 0,
  CONSTRAINT fk_venda_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id),
  CONSTRAINT fk_venda_funcionario FOREIGN KEY (funcionario_id) REFERENCES funcionarios(id)
) ENGINE=InnoDB;

CREATE TABLE itens_venda (
  id INT AUTO_INCREMENT PRIMARY KEY,
  venda_id INT NOT NULL,
  produto_id INT NOT NULL,
  lote_id INT NOT NULL,
  quantidade INT NOT NULL,
  preco_unitario_momento DECIMAL(10,2) NOT NULL,
  subtotal DECIMAL(10,2) NOT NULL,
  receita_id INT NULL,
  CONSTRAINT fk_item_venda FOREIGN KEY (venda_id) REFERENCES vendas(id),
  CONSTRAINT fk_item_produto FOREIGN KEY (produto_id) REFERENCES produtos(id),
  CONSTRAINT fk_item_lote FOREIGN KEY (lote_id) REFERENCES lotes_estoque(id),
  CONSTRAINT fk_item_receita FOREIGN KEY (receita_id) REFERENCES receitas(id)
) ENGINE=InnoDB;

CREATE INDEX idx_lote_produto_validade ON lotes_estoque(produto_id, validade);
CREATE INDEX idx_item_venda ON itens_venda(venda_id);

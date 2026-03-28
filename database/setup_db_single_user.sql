-- Setup de banco com usuario unico para a aplicacao
-- Ajuste usuario/senha conforme necessario antes de executar.

CREATE DATABASE IF NOT EXISTS farmacia_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS 'farmacia_user'@'localhost'
IDENTIFIED BY 'TROQUE_POR_UMA_SENHA_FORTE';

CREATE USER IF NOT EXISTS 'farmacia_user'@'127.0.0.1'
IDENTIFIED BY 'TROQUE_POR_UMA_SENHA_FORTE';

-- Permissao inicial para criar schema
GRANT ALL PRIVILEGES ON farmacia_db.* TO 'farmacia_user'@'localhost';
GRANT ALL PRIVILEGES ON farmacia_db.* TO 'farmacia_user'@'127.0.0.1';

FLUSH PRIVILEGES;

-- Depois de rodar database/schema.sql e database/seed.sql,
-- execute o bloco abaixo para reduzir privilegios no runtime.
REVOKE ALL PRIVILEGES, GRANT OPTION FROM 'farmacia_user'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON farmacia_db.* TO 'farmacia_user'@'localhost';

REVOKE ALL PRIVILEGES, GRANT OPTION FROM 'farmacia_user'@'127.0.0.1';
GRANT SELECT, INSERT, UPDATE, DELETE ON farmacia_db.* TO 'farmacia_user'@'127.0.0.1';

FLUSH PRIVILEGES;

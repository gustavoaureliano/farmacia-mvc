# Farmacia MVC

Sistema interno de balcao para farmacia usando PHP puro, arquitetura MVC, PDO e MariaDB.

## Requisitos

- PHP 8.1+
- Composer
- MariaDB/MySQL
- Extensoes PHP: `pdo_mysql` e `mysqli`

## Instalacao no Arch Linux (somente desenvolvimento)

1. Instale os pacotes:

```bash
sudo pacman -S php composer mariadb
```

2. Habilite as extensoes no `php.ini`:

```bash
sudo sed -i 's/;extension=mysqli/extension=mysqli/' /etc/php/php.ini
sudo sed -i 's/;extension=pdo_mysql/extension=pdo_mysql/' /etc/php/php.ini
```

Se voce usa Neovim com LSP `phpactor`, habilite tambem `iconv`:

```bash
sudo sed -i 's/;extension=iconv/extension=iconv/' /etc/php/php.ini
```

Verifique se as extensoes estao ativas:

```bash
php -m | grep -E 'mysqli|pdo_mysql|iconv'
```

3. Inicialize o MariaDB (primeira vez apenas):

```bash
sudo mariadb-install-db --user=mysql --basedir=/usr --datadir=/var/lib/mysql
sudo systemctl enable --now mariadb
sudo mariadb-secure-installation
```

4. No projeto, instale dependencias:

```bash
cd /home/gustavo/farmacia-mvc
composer install
```

5. Configure banco/usuario e schema (modelo atual):

```bash
sudo mariadb < database/setup_db_single_user.sql
mariadb -u farmacia_user -p farmacia_db < database/farmacia_db.sql
mariadb -u farmacia_user -p farmacia_db < database/seed_new_schema.sql
```

6. Configure variaveis de ambiente:

```bash
export DB_HOST=127.0.0.1
export DB_PORT=3306
export DB_NAME=farmacia_db
export DB_USER=farmacia_user
export DB_PASS=TROQUE_POR_UMA_SENHA_FORTE
```

7. Rode em desenvolvimento com servidor interno do PHP:

```bash
php -S localhost:8000 router.php
```

Acesse: `http://localhost:8000`

## Instalacao no Ubuntu 24.04

1. Instale os pacotes:

```bash
sudo apt update
sudo apt install -y php php-cli php-mysql composer mariadb-server apache2 libapache2-mod-php
```

2. Habilite e inicialize servicos:

```bash
sudo systemctl enable --now mariadb
sudo systemctl enable --now apache2
```

3. Seguranca inicial do MariaDB:

```bash
sudo mariadb-secure-installation
```

4. No projeto, instale dependencias e banco (modelo atual):

```bash
cd /home/gustavo/farmacia-mvc
composer install
sudo mariadb < database/setup_db_single_user.sql
mariadb -u farmacia_user -p farmacia_db < database/farmacia_db.sql
mariadb -u farmacia_user -p farmacia_db < database/seed_new_schema.sql
```

5. Configure variaveis de ambiente:

```bash
export DB_HOST=127.0.0.1
export DB_PORT=3306
export DB_NAME=farmacia_db
export DB_USER=farmacia_user
export DB_PASS=TROQUE_POR_UMA_SENHA_FORTE
```

## Rodar em desenvolvimento (Ubuntu 24.04)

No diretorio do projeto:

```bash
php -S localhost:8000 router.php
```

## Rodar em producao (Ubuntu 24.04 + Apache)

1. Copie o projeto para um diretorio de deploy (exemplo):

```bash
sudo mkdir -p /var/www/farmacia-mvc
sudo rsync -av --delete /home/gustavo/farmacia-mvc/ /var/www/farmacia-mvc/
sudo chown -R www-data:www-data /var/www/farmacia-mvc
```

2. Habilite o `mod_rewrite`:

```bash
sudo a2enmod rewrite
```

3. Crie o VirtualHost em `/etc/apache2/sites-available/farmacia-mvc.conf`:

```apache
<VirtualHost *:8080>
    ServerName farmacia.local
    DocumentRoot /var/www/farmacia-mvc

    <Directory /var/www/farmacia-mvc>
        Options Indexes FollowSymLinks MultiViews
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/farmacia.error.log
    CustomLog ${APACHE_LOG_DIR}/farmacia.access.log combined
</VirtualHost>
```

4. Ative o site e recarregue o Apache:

```bash
sudo a2ensite farmacia-mvc.conf
sudo systemctl reload apache2
```

5. Garanta que a porta 8080 esteja habilitada em `/etc/apache2/ports.conf`:

```apache
Listen 8080
```

Se alterar `ports.conf`, reinicie:

```bash
sudo systemctl restart apache2
```

## Troubleshooting rapido (Apache)

- Erro `Internal Server Error` com `.htaccess`: geralmente `mod_rewrite` desabilitado.
- Rode `sudo a2enmod rewrite` e reinicie: `sudo systemctl restart apache2`.
- Confira se o bloco `<Directory ...>` do VirtualHost tem `AllowOverride All`.

## Rotas principais

- `/home`
- `/produtos`
- `/estoque`
- `/receitas`
- `/clientes`
- `/funcionarios`
- `/vendas/nova`

## Scripts de banco

- `database/farmacia_db.sql`: schema oficial atual (PKs naturais, InnoDB, utf8mb4, indices).
- `database/seed_new_schema.sql`: carga recomendada para ambiente novo (dados base + complemento legado adaptado + Golgi convertido).
- `database/import_golgi_drugs.sql`: importador Golgi no formato do schema novo.
- `database/schema.sql` e `database/seed.sql`: legado (nao recomendado para inicializar ambiente novo).

## Reset rapido do banco (sem backup)

```bash
mysql -u root -p -e "DROP DATABASE IF EXISTS farmacia_db; CREATE DATABASE farmacia_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p < database/farmacia_db.sql
mysql -u root -p < database/seed_new_schema.sql
```

## Regra FEFO implementada

Ao adicionar item na venda, o sistema baixa estoque do lote com validade mais proxima primeiro (FEFO), ignorando lotes vencidos.

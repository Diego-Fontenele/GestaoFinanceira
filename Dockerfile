# Usa a imagem oficial do PHP com Apache
FROM php:8.2-apache

# Instala dependências do PostgreSQL e a extensão do PHP
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Copia os arquivos do projeto para o diretório padrão do Apache
COPY . /var/www/html/

# Ajusta as permissões (opcional)
RUN chown -R www-data:www-data /var/www/html

# Expondo a porta padrão do Apache
EXPOSE 80
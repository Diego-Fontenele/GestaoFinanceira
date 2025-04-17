# Usa a imagem oficial do PHP com Apache
FROM php:8.2-apache

# Instala dependências do PostgreSQL, o Composer, e outras dependências necessárias
RUN apt-get update && apt-get install -y \
    libpq-dev \
    curl \
    git \
    unzip \
    && docker-php-ext-install pdo pdo_pgsql

# Instala o Composer globalmente
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copia os arquivos do projeto para o diretório padrão do Apache
COPY . /var/www/html/

# Ajusta as permissões (opcional)
RUN chown -R www-data:www-data /var/www/html

# Rodar o Composer Install para instalar as dependências (incluindo o PHPMailer)
WORKDIR /var/www/html
RUN composer install

# Expondo a porta padrão do Apache
EXPOSE 80


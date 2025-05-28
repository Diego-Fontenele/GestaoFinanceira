# Usa a imagem oficial do PHP com Apache
FROM php:8.2-apache

# Instala dependências do sistema e do PHP
RUN apt-get update && apt-get install -y \
    libpq-dev \
    curl \
    git \
    unzip \
    && docker-php-ext-install pdo pdo_pgsql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Instala o Composer globalmente
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Define o diretório de trabalho
WORKDIR /var/www/html

# Copia os arquivos do projeto
COPY . .

# Ajusta as permissões
RUN chown -R www-data:www-data /var/www/html

# Instala as dependências do Composer
RUN composer install --no-interaction --prefer-dist

# Expõe a porta padrão do Apache
EXPOSE 80
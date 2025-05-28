FROM php:8.2-apache

# Instala dependências do sistema e PHP, incluindo as libs necessárias para pecl_http
RUN apt-get update && apt-get install -y \
    libpq-dev \
    curl \
    git \
    unzip \
    libcurl4-openssl-dev \
    libevent-dev \
    libssl-dev \
    pkg-config \
    libz-dev \
    autoconf \
    build-essential \
    && pecl install raphf propro \
    && pecl install pecl_http \
    && docker-php-ext-enable raphf propro http \
    && docker-php-ext-install pdo pdo_pgsql \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Instala o Composer globalmente
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www/html

COPY . .

# Ajusta permissões da pasta do projeto
RUN chown -R www-data:www-data /var/www/html

# Instala dependências do Composer
RUN composer install --no-interaction --prefer-dist

EXPOSE 80
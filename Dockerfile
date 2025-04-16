# Usa a imagem oficial do PHP com Apache
FROM php:8.2-apache

# Copia os arquivos do projeto para o diretório padrão do Apache
COPY . /var/www/html/

# Ajusta as permissões (opcional)
RUN chown -R www-data:www-data /var/www/html

# Expondo a porta padrão do Apache
EXPOSE 80
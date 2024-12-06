FROM php:8.3-fpm

# Instala dependências e extensões do PHP
RUN apt-get update && apt-get install -y \
    nginx \
    nano \
    procps \
    zip \
    git \
    htop \
    libzip-dev \
    && docker-php-ext-install zip opcache \
    && docker-php-ext-enable opcache

# Copia a configuração do OPCache
COPY opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Instala o Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copia a configuração do webservice
COPY default.conf /etc/nginx/sites-available/default

RUN mkdir -p /app
COPY app/ /app/

WORKDIR /app
RUN composer install --no-interaction --optimize-autoloader

# Copia e configura permissões do script de inicialização
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

RUN mkdir -p /app/cache

# Configura permissões base para o diretório /app
RUN chown -R www-data:www-data /app \
    && chmod -R 755 /app

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]

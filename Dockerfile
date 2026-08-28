# Imagem base oficial do PHP em Alpine (muito leve)
FROM php:8.4-fpm

# Instala dependências do sistema necessárias para as extensões do PHP
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo pdo_pgsql zip bcmath gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Instala extensões do PHP necessárias para o Laravel e PostgreSQL
RUN docker-php-ext-install pdo pdo_pgsql zip bcmath

# Copia a ferramenta Composer da imagem oficial
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copia os arquivos de gerenciamento de dependências
COPY package*.json ./
COPY composer.json composer.lock ./

# Instala dependências do Composer sem pacotes de dev (otimizado para produção)
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Copia todo o código da aplicação
COPY . .

# Finaliza a instalação das dependências gerando o autoloader do Composer
RUN composer dump-autoload --optimize

# Permissões necessárias para as pastas de escrita do Laravel
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

EXPOSE 9001

# Script de inicialização: roda caches e inicia o servidor na porta 9001
CMD php artisan config:cache \
    && php artisan route:cache \
    && php artisan serve --host=0.0.0.0 --port=9001
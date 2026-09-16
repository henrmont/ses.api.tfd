FROM php:8.4-fpm

# 1. Instala dependências do sistema, Nginx e Supervisor
RUN apt-get update && apt-get install -y --no-install-recommends \
    nginx \
    supervisor \
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

# 2. Instalação do Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# 3. Copia arquivos de dependência
COPY package*.json ./
COPY composer.json composer.lock ./

# 4. Instala dependências do Composer sem pacotes de desenvolvimento
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# 5. Copia todo o código da aplicação
COPY . .

# --- NOVO: Garante a criação do .env a partir do .env.example ---
RUN if [ -f .env.example ]; then cp .env.example .env; fi

# 6. Otimização do Autoloader
RUN composer dump-autoload --optimize \
    && php artisan config:clear || true \
    && php artisan route:clear || true

# 7. Ajuste de permissões para pastas de escrita do Laravel e para o arquivo .env
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache \
    && if [ -f /var/www/.env ]; then chown www-data:www-data /var/www/.env && chmod 664 /var/www/.env; fi

# 8. Copia as configurações do Nginx e do Supervisor
COPY ./docker/nginx.conf /etc/nginx/sites-available/default
COPY ./docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Associa o arquivo de site habilitado no Nginx
RUN rm -f /etc/nginx/sites-enabled/default \
    && ln -s /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default

# Expõe a porta 80
EXPOSE 80

# Inicializa o Supervisor que gerenciará o PHP-FPM e o Nginx em background
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
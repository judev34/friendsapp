FROM php:8.2-fpm-alpine

# Installation des dépendances système
RUN apk add --no-cache \
    git \
    unzip \
    curl \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    icu-dev \
    oniguruma-dev \
    mysql-client \
    supervisor \
    nginx

# Installation des extensions PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        zip \
        gd \
        intl \
        mbstring \
        opcache \
        bcmath

# Installation de Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configuration PHP pour production
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=256" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=20000" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "realpath_cache_size=4096K" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "realpath_cache_ttl=600" >> /usr/local/etc/php/conf.d/opcache.ini

# Configuration PHP-FPM
RUN echo "pm.max_children = 50" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "pm.start_servers = 5" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "pm.min_spare_servers = 5" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "pm.max_spare_servers = 35" >> /usr/local/etc/php-fpm.d/www.conf

# Répertoire de travail
WORKDIR /var/www/html

# Copie du code source complet
COPY . .

# Configuration Git et permissions (en tant que root)
RUN git config --global --add safe.directory /var/www/html \
    && git config --global --add safe.directory '*' \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Installation des dépendances Composer (en tant que root)
RUN composer install --no-scripts --no-dev --optimize-autoloader --ignore-platform-req=ext-amqp

# Finalisation de l'installation Composer
RUN composer dump-autoload --optimize --classmap-authoritative

# Permissions finales
RUN mkdir -p var/cache var/log \
    && chown -R www-data:www-data var \
    && chmod -R 775 var

# Configuration Symfony pour production
ENV APP_ENV=prod
ENV APP_DEBUG=0

# Exposition du port
EXPOSE 9000

# Utilisateur par défaut
USER www-data

# Commande par défaut
CMD ["php-fpm"]

FROM php:8.4-apache

WORKDIR /var/www/html

# Dépendances système + extensions PHP
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libzip-dev \
    && docker-php-ext-install pdo pdo_mysql zip

# Activer Apache rewrite (ESSENTIEL pour Symfony)
RUN a2enmod rewrite

# Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copier projet
COPY . .

# Composer install
RUN composer install --prefer-dist --optimize-autoloader

# 🔥 IMPORTANT : Apache doit pointer vers /public
ENV APACHE_DOCUMENT_ROOT /var/www/html/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Permissions Symfony
RUN chown -R www-data:www-data /var/www/html/var

COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf

RUN a2enmod rewrite

EXPOSE 80

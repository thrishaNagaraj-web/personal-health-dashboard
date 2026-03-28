FROM php:8.2-apache

# Install SQLite extensions and dependencies
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    sqlite3 \
    && docker-php-ext-install pdo pdo_sqlite

# Enable Apache mod_rewrite just in case
RUN a2enmod rewrite

# Copy application files
COPY . /var/www/html/

# Set permissions for Apache
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 775 /var/www/html

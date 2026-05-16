FROM php:8.2-apache

# Install SQLite extensions, curl, and dependencies
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    sqlite3 \
    libcurl4-openssl-dev \
    && docker-php-ext-install pdo pdo_sqlite curl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Copy application files
COPY . /var/www/html/

# Set permissions for Apache
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 775 /var/www/html

# Prepare persistent data directory
RUN mkdir -p /data

# At runtime: inject Render env vars into Apache, fix permissions, start Apache
CMD /bin/bash -c 'echo "export GROQ_API_KEY=${GROQ_API_KEY}" >> /etc/apache2/envvars && chown -R www-data:www-data /data && apache2-foreground'



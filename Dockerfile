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

# Pass environment variables through Apache to PHP
RUN echo "PassEnv GROQ_API_KEY" >> /etc/apache2/conf-enabled/passenv.conf
RUN echo "PassEnv RENDER_DISK_PATH" >> /etc/apache2/conf-enabled/passenv.conf

# Copy application files
COPY . /var/www/html/

# Set permissions for Apache
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 775 /var/www/html

# Prepare persistent data directory
RUN mkdir -p /data

# Fix permissions on runtime and start Apache
CMD chown -R www-data:www-data /data && apache2-foreground


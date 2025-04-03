FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    git \
    unzip \
    && docker-php-ext-install pdo_sqlite

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set the working directory
WORKDIR /var/www/html

# Create storage directory for SQLite
RUN mkdir -p /var/data && \
    chown -R www-data:www-data /var/data && \
    chmod -R 755 /var/data

# Copy composer files first
COPY composer.json composer.lock* ./

# Install dependencies
RUN composer install --no-dev --no-scripts --no-autoloader

# Copy application files
COPY . .

# Create default .env if it doesn't exist
RUN touch .env && \
    echo "APP_ENV=production" >> .env && \
    echo "DATABASE_PATH=/var/data/tweets.db" >> .env

# Generate autoloader
RUN composer dump-autoload --optimize

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Make the startup script executable
RUN chmod +x docker-entrypoint.sh

# Apache configuration
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Set the entrypoint
CMD ["./docker-entrypoint.sh"] 
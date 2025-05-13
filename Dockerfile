# Use PHP 8.2 with FPM
FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Set working directory
WORKDIR /app

# Create a non-root user
RUN useradd -m myuser

# Copy application files as root
COPY . .

# Fix ownership of the /app directory and its contents
RUN chown -R myuser:myuser /app

# Switch to non-root user
USER myuser

# Mark /app as a safe directory for Git
RUN git config --global --add safe.directory /app

# Install Composer dependencies
RUN composer install --optimize-autoloader --no-dev

# Switch back to root for permissions (if needed)
USER root
RUN chown -R myuser:myuser /app

# Expose port (optional, Render handles this)
EXPOSE 9000

# Start PHP-FPM
CMD ["php-fpm"]
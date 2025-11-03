FROM php:8.4-fpm-alpine

# Upgrade all packages to address vulnerabilities and remove cache
RUN apk update && apk upgrade --no-cache

# Install Symfony CLI
COPY --link \
    --from=ghcr.io/symfony-cli/symfony-cli:latest \
    /usr/local/bin/symfony /usr/local/bin/symfony

# Install dependencies for PHP extensions
RUN apk add --no-cache $PHPIZE_DEPS mysql-dev

# Install PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# Set working directory
WORKDIR /var/www/html

# Expose the Symfony local server port
EXPOSE 8000

# Run the Symfony server by default
CMD ["symfony", "server:start", "--no-tls", "--allow-http", "--listen-ip=0.0.0.0"]
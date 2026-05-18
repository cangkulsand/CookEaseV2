# Use official PHP image
FROM php:8.2-fpm

# Install dependencies
RUN apt-get update && apt-get install -y \
    git curl zip unzip libpng-dev libonig-dev libxml2-dev \
    libzip-dev nodejs npm \
    && docker-php-ext-install pdo_mysql mbstring zip bcmath gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy application files
COPY . .

# Clear frontend build if exist
RUN rm -rf node_modules public/build resources/js/.vite

# Install PHP and Node dependencies
# NOTE: dev dependencies (Pint, Pest, etc.) are intentionally included here
# because this Dockerfile is the local-dev image. A separate Dockerfile.prod
# will exclude dev deps for production builds (see docs/devops-with-docker.md §5).
RUN composer install --optimize-autoloader
RUN npm install && npm run build

# Fix permissions
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www/storage /var/www/bootstrap/cache

# Expose port
EXPOSE 8000

# RUN TIME commands (dev image — leaves config uncached so .env / phpunit.xml
# env overrides work correctly for tests and hot config changes).
CMD php artisan config:clear && \
    php artisan route:clear && \
    php artisan view:clear && \
    php artisan migrate --force && \
    php artisan db:seed --class=RecipeSeeder --force && \
    php artisan db:seed --class=IngredientSeeder --force && \
    php artisan storage:link && \
    php artisan serve --host=0.0.0.0 --port=8000

# Use the official PHP 8.2 FPM image as a base
FROM php:8.2-fpm

# Install required dependencies for PHP extensions
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libxml2-dev \
    libssl-dev \
    libcurl4-openssl-dev \
    libicu-dev \
    libzip-dev \
    zip \
    git \
    libonig-dev \
	build-essential \
	libtool \
    pkg-config && \
    apt-get clean

# Install PHP extensions incrementally
RUN docker-php-ext-install ctype
RUN docker-php-ext-install curl
RUN docker-php-ext-install dom
RUN docker-php-ext-install fileinfo
RUN docker-php-ext-install filter
RUN docker-php-ext-install hash
RUN docker-php-ext-install mbstring
RUN docker-php-ext-install openssl
RUN docker-php-ext-install pcre
RUN docker-php-ext-install pdo
RUN docker-php-ext-install pdo_mysql
RUN docker-php-ext-install session
RUN docker-php-ext-install tokenizer
RUN docker-php-ext-install xml

# Install GD with required libraries (freetype and jpeg)
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd

# Install Sodium extension
RUN docker-php-ext-install sodium

# Install Xdebug via PECL (if required)
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

# Set the working directory to the Laravel app folder
WORKDIR /var/www

# Copy the application code into the container
COPY . /var/www

# Install Composer globally
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Expose port 9000 for PHP-FPM
EXPOSE 9000

# Start PHP-FPM service
CMD ["php-fpm"]

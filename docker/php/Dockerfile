FROM php:7.4-apache

RUN apt-get update
# Install system dependencies
RUN apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    zip \
    unzip \
    --no-install-recommends

# Install required modules
RUN docker-php-ext-install pdo pdo_mysql mysqli mbstring exif pcntl bcmath gd
RUN a2enmod rewrite
FROM php:8.2-apache
COPY . /var/www/html/
RUN docker-php-ext-install pdo pdo_mysql
RUN echo "DirectoryIndex index.php index.html" >> /etc/apache2/apache2.conf
# Install tools & ekstensi
RUN apt-get update && apt-get install -y \
    git \
    zip \
    unzip \
    whois \
    iputils-ping \
    && docker-php-ext-install mysqli \
    && rm -rf /var/lib/apt/lists/*
# Set timezone dalam container
ENV TZ=Asia/Jakarta



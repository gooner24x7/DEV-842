FROM php:8.3-fpm
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    wget \
    ntpsec \
    gnupg \
    zlib1g-dev \
    sendmail \
    libpng-dev \
    librdkafka-dev \
    libmagickwand-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    poppler-utils

ENV NODE_VERSION 20

RUN curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.7/install.sh | bash \
    && . /root/.nvm/nvm.sh \
    && nvm install $NODE_VERSION \
    && nvm alias default $NODE_VERSION \
    && nvm use default

COPY config/php.ini /usr/local/etc/php/

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer --version

# Set timezone
RUN rm /etc/localtime
RUN ln -s /usr/share/zoneinfo/Europe/Paris /etc/localtime
RUN "date"

# Type docker-php-ext-install to see available extensions
RUN docker-php-ext-install pdo_mysql && docker-php-ext-enable pdo_mysql
RUN docker-php-ext-install dom mbstring
RUN docker-php-ext-install zip && docker-php-ext-enable zip
RUN docker-php-ext-install gd && docker-php-ext-enable gd
RUN docker-php-ext-install bcmath && docker-php-ext-enable bcmath
RUN pecl install imagick && docker-php-ext-enable imagick
RUN pecl install rdkafka
RUN docker-php-ext-enable rdkafka

COPY config/policy.xml /etc/ImageMagick-6/

RUN pecl install -o -f redis \
&&  rm -rf /tmp/pear \
&&  docker-php-ext-enable redis

FROM php:8.1-fpm

RUN apt-get update \
    && apt-get install -y iputils-ping vim make

RUN apt-get install -y libzip-dev \
    && docker-php-ext-install zip

RUN apt-get install -y libonig-dev \
    && docker-php-ext-install mbstring

RUN docker-php-ext-install pdo pdo_mysql

# install and configure Composer
RUN apt-get install -y unzip git
ENV COMPOSER_DEFAULT_VERSION=2 \
	COMPOSER1_VERSION=1.10.25 \
	COMPOSER2_VERSION=2.2.7
ENV	COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_MEMORY_LIMIT=-1
RUN curl -fsSL "https://github.com/composer/composer/releases/download/${COMPOSER1_VERSION}/composer.phar" -o /usr/local/bin/composer1; \
    curl -fsSL "https://github.com/composer/composer/releases/download/${COMPOSER2_VERSION}/composer.phar" -o /usr/local/bin/composer2; \
    # Set Default Composer Version
    ln -s /usr/local/bin/composer${COMPOSER_DEFAULT_VERSION} /usr/local/bin/composer; \
    # Make downloaded binaries executable in one shot
    (cd /usr/local/bin && chmod +x composer1 composer2); \
    # Add composer bin directory to PATH
    echo -e "\n"'export PATH="$PATH:$HOME/.composer/vendor/bin"' >> $HOME/.profile;

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

ADD ./php.ini /usr/local/etc/php/php.ini

RUN sed -i 's/9000/9007/' /usr/local/etc/php-fpm.d/zz-docker.conf

WORKDIR /var/www

RUN #chown -R www-data:www-data /var/www
USER www-data

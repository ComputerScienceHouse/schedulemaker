FROM php:7.1-apache
LABEL author="Devin Matte <matted@csh.rit.edu>"

RUN apt-get -yq update && \
    apt-get -yq install gnupg libmagickwand-dev git gcc make autoconf libc-dev pkg-config --no-install-recommends

RUN docker-php-ext-install mysqli && \
    yes '' | pecl install imagick && docker-php-ext-enable imagick

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY apache-config.conf /etc/apache2/sites-enabled/000-default.conf

RUN a2enmod rewrite && a2enmod headers && a2enmod expires && \
    sed -i '/Listen/{s/\([0-9]\+\)/8080/; :a;n; ba}' /etc/apache2/ports.conf && \
    chmod og+rwx /var/lock/apache2 && chmod -R og+rwx /var/run/apache2

COPY . /var/www/html

RUN curl -sL https://deb.nodesource.com/setup_10.x | bash - \
    && apt-get -yq update \
    && apt-get -yq install nodejs --no-install-recommends \
    && npm install \
    && npm run-script build \
    && apt-get -yq remove nodejs \
    && apt-get -yq clean all \
    && rm -rf node_modules

RUN composer install

EXPOSE 8080
EXPOSE 8443

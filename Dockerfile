FROM php:7.1-apache
MAINTAINER Devin Matte <matted@csh.rit.edu>

EXPOSE 8080
EXPOSE 8443

RUN a2enmod rewrite && a2enmod headers && a2enmod expires

RUN sed -i '/Listen/{s/\([0-9]\+\)/8080/; :a;n; ba}' /etc/apache2/ports.conf

ADD apache-config.conf /etc/apache2/sites-enabled/000-default.conf

RUN apt-get -yq update && \
    apt-get -yq install gnupg libmagickwand-dev --no-install-recommends

RUN docker-php-ext-install mysqli
RUN pecl install imagick && docker-php-ext-enable imagick

COPY . /var/www/html

RUN curl -sL https://deb.nodesource.com/setup_6.x | bash - && \
    apt-get -yq update && \
    apt-get -yq install nodejs && \
    npm install && \
    npm run-script build && \
    rm -rf node_modules && \
    apt-get -yq remove nodejs npm && \
    apt-get -yq clean all

RUN chmod og+rwx /var/lock/apache2 && chmod -R og+rwx /var/run/apache2

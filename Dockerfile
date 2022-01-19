FROM docker.io/node:12-buster-slim as builder
LABEL author="Devin Matte <matted@csh.rit.edu>"

WORKDIR /usr/src/schedule
COPY package.json ./

RUN npm install

COPY package.json tsconfig.json gulpfile.js ./
COPY assets ./assets
RUN npm run-script build


FROM docker.io/php:7.3-apache
LABEL author="Devin Matte <matted@csh.rit.edu>"

RUN echo "deb-src http://deb.debian.org/debian buster main" >> /etc/apt/sources.list

RUN apt-get -yq update && \
    apt-get -yq install \
        gnupg \
        libmagickwand-dev \
        git \
        gcc \
        make \
        autoconf \
        libc-dev \
        pkg-config \
        build-essential \
        libx11-dev \
        libxext-dev \
        zlib1g-dev \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
        libxml2-dev \
        unzip \
        wget \
        --no-install-recommends

RUN apt-get -yq build-dep imagemagick

RUN wget https://github.com/ImageMagick/ImageMagick6/archive/6.9.11-22.tar.gz && \
    tar -xzvf 6.9.11-22.tar.gz && \
    cd ImageMagick6-6.9.11-22 && \
    ./configure && \
    make && \
    make install && \
    ldconfig /usr/local/lib && \
    make check

RUN docker-php-ext-install mysqli && \
    yes '' | pecl install imagick && docker-php-ext-enable imagick
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY apache-config.conf /etc/apache2/sites-enabled/000-default.conf

RUN a2enmod rewrite && a2enmod headers && a2enmod expires && \
    sed -i '/Listen/{s/\([0-9]\+\)/8080/; :a;n; ba}' /etc/apache2/ports.conf && \
    chmod og+rwx /var/lock/apache2 && chmod -R og+rwx /var/run/apache2

COPY . /var/www/html
COPY --from=builder /usr/src/schedule/assets/prod /var/www/html/assets/prod

RUN composer install

EXPOSE 8080
EXPOSE 8443

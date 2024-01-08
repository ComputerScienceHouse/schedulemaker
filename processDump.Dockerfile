FROM php:7.4

RUN docker-php-ext-install mysqli

WORKDIR /app

COPY inc ./inc

COPY inc/config.env.php inc/config.php

COPY tools ./tools

ENTRYPOINT ["php", "/app/tools/processDump.php", "-d"]


FROM php:5.6-fpm

# install phpredis extension
ENV PHPREDIS_VERSION 2.2.7

RUN apt-get update && apt-get install -y \
    git cron\
    # This package is required for mongo
    libssl-dev \
    # gd dependency
    libgd2-xpm-dev* \
    # install gd
    && docker-php-ext-install gd \
    # Phalcon
    && git clone --depth=1 -b 2.0.x git://github.com/phalcon/cphalcon.git /usr/local/src/cphalcon \
    && cd /usr/local/src/cphalcon/build && ./install \
    && echo "extension=phalcon.so" > /usr/local/etc/php/conf.d/25-phalcon.ini \
    # Mongo
    && pecl install mongo \
    && echo "extension=mongo.so" > /usr/local/etc/php/conf.d/25-mongo.ini

# Inject php conf
COPY config/php.ini /usr/local/etc/php/

# Add crontab file in the cron directory
COPY config/crontab /crontab_file

COPY ./entrypoint.sh /

CMD ["/entrypoint.sh"]
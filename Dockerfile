FROM php:8.4-fpm-alpine
ENV TZ=Asia/Yangon
#COPY repositories /etc/apk/repositories

# Install necessary dependencies
RUN apk --no-cache add \
    bash \
    curl \
    git \
    libzip-dev \
    libpng-dev \
    libpng \
    libzip \
    libjpeg-turbo \
    libjpeg-turbo-dev \
    freetype-dev \
    freetype \
    openssl \
    supervisor \
    imagemagick \
    imagemagick-dev \
    autoconf \
    gcc \
    g++ \
    make \
    vim \
    && rm -rf /var/cache/apk/*

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_mysql zip
ENV USER_ID=1004
ENV GROUP_ID=1005
ENV USER_NAME=appown
ENV GROUP_NAME=appown
    
RUN addgroup -g $GROUP_ID $GROUP_NAME && \
    adduser --shell /sbin/nologin --disabled-password \
    --uid $USER_ID --ingroup $GROUP_NAME $USER_NAME

RUN apk add --no-cache autoconf
RUN pecl install imagick \
    && docker-php-ext-enable imagick

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && rm -rf /root/.composer /var/www/.composer

WORKDIR /var/www/

COPY ./ / /var/www/
COPY ./docker/config/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
RUN composer config -g repos.packagist composer https://packagist.jp
RUN composer install --ignore-platform-reqs --no-dev \
    && rm -rf /root/.composer /var/www/.composer /var/cache/apk/*
RUN chown -R appown:appown /var/www/
RUN chmod -R 777 /var/www/
USER $USER_NAME
EXPOSE 9000

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

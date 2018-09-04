FROM php:7.1.3-fpm

#install all the system dependencies and enable PHP modules
RUN apt-get update && apt-get install -y \
      libicu-dev \
      libpq-dev \
      libmcrypt-dev \
      libpng-dev \
      git \
      zip \
      unzip \
    && rm -r /var/lib/apt/lists/* \
    && docker-php-ext-configure pdo_mysql --with-pdo-mysql=mysqlnd \
    && docker-php-ext-install \
      intl \
      mbstring \
      mcrypt \
      pcntl \
      pdo_mysql \
      pdo_pgsql \
      pgsql \
      zip \
      opcache \
      gd

RUN apt-get update
RUN apt-get install -y supervisor nano cron
RUN apt-get install -y python-pip python-dev build-essential 
RUN pip install --upgrade pip 

# AWS for backup
RUN pip install awscli --upgrade --user

# install mysqldump
RUN apt-get install -y mysql-client

#install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin/ --filename=composer

COPY composer.lock composer.json /var/www/

COPY database /var/www/database

WORKDIR /var/www

# install all PHP dependencies
RUN composer install --no-interaction --no-dev

# Add crontab file in the cron directory
ADD crontab /etc/cron.d/cron
RUN touch /var/log/cron.log

# Give execution rights on the cron job
RUN chmod 0644 /etc/cron.d/cron

COPY . /var/www

RUN mkdir -p /var/www/public/uploads

RUN chown -R www-data:www-data \
        /var/www/storage /var/www/public/uploads

# setup timezone
RUN echo "Asia/Almaty" > /etc/timezone
RUN rm /etc/localtime
RUN dpkg-reconfigure -f noninteractive tzdata
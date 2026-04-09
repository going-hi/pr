FROM php:8.3-apache

RUN docker-php-ext-install pdo_mysql \
    && a2enmod rewrite

RUN printf '%s\n' \
    'default_charset = UTF-8' \
    'upload_max_filesize = 8M' \
    'post_max_size = 9M' \
    > /usr/local/etc/php/conf.d/charset.ini

RUN printf '%s\n' \
    '<Directory /var/www/html>' \
    '    AllowOverride All' \
    '    Require all granted' \
    '</Directory>' \
    > /etc/apache2/conf-available/allow-override.conf \
    && a2enconf allow-override

RUN mkdir -p /var/www/html/uploads/recipes \
    && chown -R www-data:www-data /var/www/html/uploads

WORKDIR /var/www/html

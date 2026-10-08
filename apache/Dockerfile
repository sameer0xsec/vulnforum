FROM php:8.2-apache
RUN docker-php-ext-install mysqli
RUN a2enmod rewrite
# VULN: Apache's default "Indexes" behaviour + php.ini left at dev-friendly
# defaults — display_errors on, expose_php on — baked into the image.
RUN { \
    echo "display_errors = On"; \
    echo "display_startup_errors = On"; \
    echo "expose_php = On"; \
    echo "session.use_strict_mode = 0"; \
  } > /usr/local/etc/php/conf.d/vuln-settings.ini
COPY apache-vhost.conf /etc/apache2/sites-available/000-default.conf

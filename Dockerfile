FROM php:8.3-apache
ARG VERSION=dev
LABEL org.opencontainers.image.version=$VERSION
RUN a2enmod rewrite
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY src/ /var/www/html/
COPY VERSION /var/www/html/VERSION

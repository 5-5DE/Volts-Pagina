FROM php:8.4-apache

RUN docker-php-ext-install pdo_mysql \
    && a2dismod mpm_event mpm_worker \
    && a2enmod mpm_prefork headers \
    && a2dissite 000-default

WORKDIR /var/www/html
COPY index.php catalogo-paquetes.php ./
COPY acciones/ acciones/
COPY assets/ assets/
COPY config/ config/
COPY includes/ includes/
COPY paginas/ paginas/
COPY scripts/migrar-solicitudes.php /opt/volts/migrar-solicitudes.php
COPY deploy/volts.conf /etc/apache2/sites-enabled/volts.conf

ENV PORT=8080 APACHE_DOCUMENT_ROOT=/var/www/html
RUN printf 'Listen ${PORT}\n' > /etc/apache2/ports.conf && apache2ctl configtest
CMD ["sh", "-c", "php /opt/volts/migrar-solicitudes.php && exec apache2-foreground"]

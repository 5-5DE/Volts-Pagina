FROM dunglas/frankenphp:1-php8.4-trixie

RUN install-php-extensions pdo_mysql \
    && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

WORKDIR /app/public
COPY index.php catalogo-paquetes.php ./
COPY acciones/ acciones/
COPY assets/ assets/
COPY config/ config/
COPY includes/ includes/
COPY paginas/ paginas/
COPY scripts/migrar-solicitudes.php /opt/volts/migrar-solicitudes.php
COPY deploy/Caddyfile /etc/frankenphp/Caddyfile

ENV PORT=8080 VOLTS_APP_ROOT=/app/public
RUN frankenphp validate --config /etc/frankenphp/Caddyfile --adapter caddyfile
ENTRYPOINT ["sh", "-c", "php /opt/volts/migrar-solicitudes.php && exec frankenphp run --config /etc/frankenphp/Caddyfile --adapter caddyfile"]

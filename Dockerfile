FROM php:8.2-cli

WORKDIR /app

COPY helpers.php index.php cart.php payment.php admin.php ./

EXPOSE 10000

CMD php -S 0.0.0.0:${PORT:-10000} -t /app

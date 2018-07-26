FROM debian:latest

MAINTAINER Roman Ondráček <roman.ondracek@iqrf.com>
LABEL maintainer="roman.ondracek@iqrf.com"

RUN apt-get update \
 && apt-get install --no-install-recommends -y composer git php7.0 php7.0-common php7.0-curl php7.0-fpm php7.0-json php7.0-mbstring php7.0-sqlite php7.0-zip unzip \
 && apt-get clean \
 && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/iqrf-gateway-webapp

RUN git clone https://github.com/iqrfsdk/iqrf-gateway-webapp .
RUN composer install
RUN sed -i 's/sudo\:\ true/sudo\:\ false/g' app/config/config.neon
RUN sed -i "s/initDaemon: 'systemd'/initDaemon: 'docker'/g" app/config/config.neon
RUN chmod 777 log/ \
 && chmod 777 temp/

CMD [ "php", "-S", "[::]:8080", "-t", "/var/www/iqrf-gateway-webapp/www/" ]

EXPOSE 8080

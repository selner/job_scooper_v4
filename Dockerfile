#FROM sandrokeil/php:5.6-cli-xdebug
FROM buonzz/php-production-cli:latest

ENV DEBIAN_FRONTEND=noninteractive

RUN pecl install xdebug

RUN docker-php-ext-enable xdebug
RUN echo "zend_extension=/usr/lib/php/20160303/xdebug.so" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
RUN echo "xdebug.remote_enable = 1" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
RUN echo "xdebug.default_enable = 0" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
RUN echo "xdebug.remote_autostart = 1" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
RUN echo "xdebug.remote_handler=dbgp" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
RUN echo "xdebug.remote_mode=req" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
RUN echo "xdebug.remote_port=10000" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
RUN echo "xdebug.remote_log=/var/log/xdebug_remote.log" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
RUN echo "xdebug.idekey=PHPSTORM" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
RUN echo "xdebug.remote_connect_back = 0" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
RUN echo "xdebug.profiler_enable = 0" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
RUN echo "xdebug.remote_host = 192.168.24.202" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

EXPOSE 22
EXPOSE 9000

VOLUME "/var/local/jobs_scooper" "/var/local/jobs_scooper"
VOLUME "/var/opt/" "/var/opt"
VOLUME "/Users/bryan/code" "/code"
WORKDIR "/var/opt/jobs_scooper/src"

ENTRYPOINT “scoop_today_v4_stage1.sh”


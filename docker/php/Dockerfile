FROM ulsmith/alpine-apache-php7

# fix work iconv library with alphine
RUN apk add --no-cache --repository http://dl-cdn.alpinelinux.org/alpine/edge/community/ --allow-untrusted gnu-libiconv
ENV LD_PRELOAD /usr/lib/preloadable_libiconv.so php

# install htpasswd
RUN apk add apache2-utils

RUN echo "error_log = /logs/error.log" >> /etc/php7/php.ini

# add umask 0002 to container - to create files in 664 mode
RUN sed -i '$i umask 0002' /bootstrap/start.sh

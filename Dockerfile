# Pull base image.
FROM leeyenmomoko/dockerfile-ubuntu-php7-nginx

MAINTAINER Lee Yen <lee.yen@eztable.com>

RUN cd /var/www/html &&\
    git clone https://github.com/leeyenmomoko/epubMaker.git

RUN  sed -i "s/\/var\/www\/html/\/var\/www\/html\/epubMaker/g" /etc/nginx/sites-enabled/default

EXPOSE 10082
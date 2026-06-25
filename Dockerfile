FROM php:8.2-apache
RUN a2enmod rewrite
# 修改默认首页优先级，优先index.php
RUN sed -i 's/DirectoryIndex index.html/DirectoryIndex index.php index.html/' /etc/apache2/mods-enabled/dir.conf
COPY . /var/www/html/
EXPOSE 80

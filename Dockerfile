FROM php:8.2-apache
# 开启Apache重写模块
RUN a2enmod rewrite
# 修改默认首页优先级，优先读取index.php
RUN sed -i 's/DirectoryIndex index.html/DirectoryIndex index.php index.html/' /etc/apache2/mods-enabled/dir.conf
# 复制全部项目文件到网站目录
COPY . /var/www/html/
EXPOSE 80

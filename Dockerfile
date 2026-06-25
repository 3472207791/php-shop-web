FROM php:8.2-apache
# 开启PHP重写模块
RUN a2enmod rewrite
# 设置默认首页优先index.php
RUN echo "DirectoryIndex index.php index.html" >> /etc/apache2/mods-enabled/dir.conf
# 复制项目代码到网站目录
COPY . /var/www/html/
# 开放80端口
EXPOSE 80

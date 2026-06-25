# 使用官方PHP Apache镜像
FROM php:8.2-apache
# 把仓库所有代码复制到网站目录
COPY . /var/www/html/
# 开放80网页端口
EXPOSE 80

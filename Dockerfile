# 使用PHP 7.4 FPM Alpine作为基础镜像
FROM php:8.3-fpm-alpine as php

# 设置工作目录
WORKDIR /var/www/
ENV TZ="Asia/Shanghai"
USER root

# 安装PHP扩展（根据需要安装）
RUN set -eux; \
    apk add --no-cache \ 
    nginx; \
    sed  -i  '$a listen.owner = nginx' /usr/local/etc/php-fpm.d/zz-docker.conf; \
    sed  -i  '$a listen.group = nginx' /usr/local/etc/php-fpm.d/zz-docker.conf; \
    sed  -i  '8i php-fpm -D' /usr/local/bin/docker-php-entrypoint;

# 复制自定义的Nginx配置文件到容器中
COPY ./config/nginx-dockerfile.conf /etc/nginx/nginx.conf

# 复制应用代码到容器中
COPY ./www /var/www/html

# 暴露端口
EXPOSE 80

# 启动Nginx服务器
CMD ["nginx", "-g", "daemon off;"]

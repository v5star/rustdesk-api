# 使用php:7.4.33作为基础镜像
FROM php:7.4.33-fpm

# 设置工作目录
WORKDIR /var/www/

# 安装nginx并清理安装过程中的不必要文件
RUN apt-get update && apt-get install -y nginx net-tools\
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* 

# 将Nginx配置文件替换为自定义的配置文件
COPY ./config/nginx.conf /etc/nginx/nginx.conf
COPY ./config/php-fpm.conf /usr/local/etc/php-fpm.d/zz-docker.conf
COPY start.sh . 

# 将PHP文件复制到工作目录(mysql的本镜像不合适)
COPY sqlite/ /var/www/html/

# 暴露端口
EXPOSE 9001

# 启动Nginx服务器
CMD ["nginx", "-g", "daemon off;"]
ENTRYPOINT ["bash","start.sh"]

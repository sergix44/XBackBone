FROM webdevops/php-nginx:8.2-alpine
MAINTAINER Sergio <sergio@brighenti.me>

ARG XBACKBONE_VERSION=3.6.4
ENV APP_NAME=XBackBone
ENV URL=http:\/\/127.0.0.1

ADD ./nginx.conf /opt/docker/etc/nginx/vhost.common.d/11-xbackbone.conf
ADD ./configure.sh /opt/docker/provision/entrypoint.d/01-app.sh

USER application

RUN wget "https://github.com/SergiX44/XBackBone/releases/download/${XBACKBONE_VERSION}/release-v${XBACKBONE_VERSION}.zip" -O /app/master.zip; \
        cd /app; \
        unzip master.zip; \
        rm master.zip; \
	mkdir -p /app/config

VOLUME [ "/app/storage", "/app/resources/database", "/app/logs", "/app/config"]

#Fix #3
USER root

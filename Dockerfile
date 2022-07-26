FROM ubuntu:focal

LABEL maintainer="gpopesc@gmail.com"

ARG DEBIAN_FRONTEND=noninteractive
ARG LANG=us_US.UTF-8
ARG LANGUAGE=us_US.UTF-8

ENV DEBIAN_FRONTEND=${DF} \
    LANG=${LANG} \ 
    LANGUAGE=${LANGUAGE} \
    TZ=${TZ} \
    USER_NAME=www-data \
    UID=33 \
    GID=33 \
    JITSI_SERVER=${JITSI_SERVER} \
    #JWT_ALG=${JWT_ALG} \
    JWT_SECRET=${JWT_SECRET} \
    JWT_ISS=${JWT_ISS} \
    JWT_AUD=${JWT_AUD} \
    JWT_SUB=${JWT_SUB} \
    EMAIL_SERVER=${EMAIL_SERVER} \
    EMAIL_PORT=${EMAIL_PORT} \
    SMTPAUTH=${SMTPAUTH} \
    SMTPSECURE=${SMTPSECURE} \
    EMAIL_USERNAME=${EMAIL_USERNAME} \
    EMAIL_PASS=${EMAIL_PASS} \
    SENDER_EMAIL=${SENDER_EMAIL} \
    SENDER_NAME=${SENDER_NAME} \
    EMAIL_SIGNATURE=${EMAIL_SIGNATURE} \
    YRL_USERNAME=${YRL_USERNAME} \
    YRL_PASSWORD=${YRL_PASSWORD} \
    YRL_API_URL=${YRL_API_URL} \
    JWT_SERVER_NAME=${JWT_SERVER_NAME}



# mandatory apps
RUN apt-get update && apt-get -y install wget \
      curl \
      tzdata \
      apache2 \
      php7.4 \
#    php7.4-common php7.4-curl php7.4-cli php7.4-dev php7.4-opcache php7.4-zip php7.4-intl \
      php7.4-curl \
   && rm -rf /var/lib/apt/lists/*

#set working directory to where Apache serves files
WORKDIR /var/www/html
EXPOSE  80

HEALTHCHECK --interval=1m --timeout=10s CMD curl --fail http://127.0.0.1:80

#config files to temp location
COPY html/ /var/www/html


COPY startup.sh /
RUN chmod +x /startup.sh

CMD ["/startup.sh"]


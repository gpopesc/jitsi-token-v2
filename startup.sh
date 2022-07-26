#!/bin/bash
exec >> /tmp/start.log 2>&1


echo "Running as user ${USER_NAME} with id ${UID} and groupid ${GID}"


a2enmod rewrite

tee -a /etc/apache2/sites-available/000-default.conf << EOF
<Directory /var/www/html>
	Options Indexes FollowSymLinks MultiViews
	AllowOverride All
	Require all granted
</Directory>
<FilesMatch \.php$>
	SetHandler application/x-httpd-php
</FilesMatch>
EOF

# enable php short tags:
/bin/sed -i "s|short_open_tag =  Off|short_open_tag = On|g" /etc/php/7.4/apache2/php.ini
# Set PHP timezone
/bin/sed -i 's|;date.timezone =|date.timezone = '${TZ}'|g' /etc/php/7.4/apache2/php.ini

chown www-data:www-data -R /var/www/html/*
chmod 777 -R /var/www/html/*

#sh /var/www/html/script/deno.sh

sh /usr/sbin/apache2ctl -D FOREGROUND
echo "===========> script finnished <============"


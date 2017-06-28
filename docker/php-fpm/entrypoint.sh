#!/bin/bash

touch /logs/php_errors.log

chown www-data:www-data /logs/php_errors.log

crontab /crontab_file

cron

php-fpm
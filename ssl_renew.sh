#!/bin/bash

COMPOSE="/usr/bin/docker-compose --no-ansi"
uOCKER="/usr/bin/docker"

cd /home/docker-images/docker-wordpress/
$COMPOSE run certbot renew && $COMPOSE kill -s SIGHUP webserver
$DOCKER system prune -af

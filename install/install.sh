#!/bin/bash

### General APT installation

CurrentDir=$(pwd)

apt update
apt -y install software-properties-common

### Install MC (optional)

apt -y install mc

### Install Webmin

apt -y install perl libnet-ssleay-perl openssl libauthen-pam-perl libpam-runtime libio-pty-perl apt-show-versions python
echo "deb http://download.webmin.com/download/repository sarge contrib" >> /etc/apt/sources.list
cd /root
wget http://www.webmin.com/jcameron-key.asc
apt-key add jcameron-key.asc
apt update
apt -y install apt-transport-https
apt -y install webmin
apt -y install libio-socket-inet6-perl

cd $CurrentDir
pwd

### Install smartmontools

apt -y install smartmontools

### Install mysql

apt -y install mysql-server

### Install nginx

apt -y install nginx

### PHP

apt -y install php php-fpm curl php-curl php-gd php-mbstring php-mysqli php-mysqlnd php-zip php-http php-dom php-xml php-exif
apt -y remove apache2

#apt -y install php7.4-fpm php7.4-curl php7.4-gd php7.4-mbstring php7.4-mysqli php7.4-mysqlnd php7.4-zip php7.4-http php7.4-dom php7.4-xml php7.4-exif

### Install Git

apt -y install git

### Install At without which the worker heartbeat services will not work

apt -y install at

### Install AWStats

apt -y install awstats

### Let's Encrypt certbot

apt -y install certbot
apt -y install python3-certbot-nginx

### NPM

apt -y install npm

### Run the PHP installer script

php ./install.php

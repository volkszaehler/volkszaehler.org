#!/bin/bash
#
# Installer
#
# For creating/updating the configuration/database
# and downloading of required libraries
# and configuration of of the PHP interpreter/webserver
#
# @copyright Copyright (c) 2011, The volkszaehler.org project
# @package tools
# @license http://www.opensource.org/licenses/gpl-license.php GNU Public License
# @author Jakob Hirsch
#
##
# This file is part of volkzaehler.org
#
# volkzaehler.org is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# any later version.
#
# volkzaehler.org is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with volkszaehler.org. If not, see <http://www.gnu.org/licenses/>.
##

set -e
shopt -s nocasematch

###############################
# some configuration

# please update after releases
dt_tar=http://www.doctrine-project.org/downloads/DoctrineORM-2.2.0-full.tar.gz
vz_tar=https://github.com/volkszaehler/volkszaehler.org/tarball/master
#vz_tar=http://wiki.volkszaehler.org/_media/software/releases/volkszaehler.org-0.2.tar.gz

# cannot handle other hosts right now
db_host=localhost

tmp_dir=$(mktemp -d)

###############################
# functions
ask() {
	question=$1
	default=$2
	read -e -p "$question [$default] "
	REPLY=${REPLY:-$default}
}

cleanup() {
	if [ -e $tmp_dir ]; then
		rm -r $tmp_dir
	fi
}

get_admin() {
	test -n "$db_admin_user" && return
	ask "mysql admin user?" root
	db_admin_user=$REPLY
	sed -i -e "s/^\/*\(\$config\['db'\]\['admin'\]\['user'\]\).*/\1 = '$db_admin_user';/" $config
	ask "mysql admin password?"
	db_admin_pass=$REPLY
	sed -i -e "s/^\/*\(\$config\['db'\]\['admin'\]\['password'\]\).*/\1 = '$db_admin_pass';/" $config
}

get_db_name() {
	test -n "$db_name" && return
	ask "mysql database?" volkszaehler
	db_name=$REPLY
}

###############################
# header
echo "volkszaehler.org installation script"

###############################
# check prerequisites
echo
echo -n "checking prerequisites:"

deps=( php mysql awk sed grep wget mktemp mkdir tar )

for binary in "${deps[@]}"; do
	if [ `which $binary` ] ; then
		echo -n " $binary"
	else
		echo
		echo "you need $binary to run this installer"
		cleanup && exit 1
	fi
done
echo

php_major=`php --version | grep "^PHP" | awk ' { print $2 } ' | cut -b 1 `
php_minor=`php --version | grep "^PHP" | awk ' { print $2 } ' | cut -b 3 `

echo "php version: $php_major.$php_minor"

if [ "$php_major" -lt "5" ] ; then
	echo "you need PHP version 5.3+ to run volkszaehler"
	cleanup && exit 1
elif [ "$php_major" == "5" ] ; then
	if [ "$php_minor" -lt "3" ] ; then
		echo "you need PHP version 5.3+ to run volkszaehler"
		cleanup && exit 1
	fi
fi

###############################
echo
echo "doctrine setup..."

ask "doctrine path?" /usr/local/lib/doctrine-orm
dt_dir=$REPLY

if [ -e "$dt_dir" ]; then
	ask "$dt_dir already exists. overwrite?" n
else
	mkdir $dt_dir
	REPLY=y
fi

if [ "$REPLY" == 'y' ]; then
	echo "installing doctrine into $dt_dir"
	wget -O - $dt_tar | tar xz -C $tmp_dir
	cp -r $tmp_dir/Doctrine*/Doctrine/* $dt_dir/
fi

###############################
echo
echo "volkszaehler setup..."

ask "volkszaehler path?" /var/www/volkszaehler.org
vz_dir=$REPLY

if [ -e "$vz_dir" ]; then
	ask "$vz_dir already exists. overwrite?" n
else
	mkdir $vz_dir
	REPLY=y
fi

if [ "$REPLY" == 'y' ]; then
	echo "installing volkszaehler.org into $vz_dir"
	wget -O - $vz_tar | tar xz -C $tmp_dir
	cp -r $tmp_dir/volkszaehler*/* $vz_dir/
fi

###############################
echo
ask "configure volkszaehler.org?" y

config=$vz_dir/etc/volkszaehler.conf.php

if [ "$REPLY" == "y" ]; then
	# test for pdo_mysql php module
	php -m | grep pdo_mysql > /dev/null
	if [ $? -ne 0 ]; then
		echo "php module pdo_mysql has not been found"
		echo "try 'sudo apt-get install php5-mysql' on Debian/Ubuntu based systems"
		cleanup && exit 1
	fi
	
	ask "mysql user?" vz
	db_user=$REPLY
	ask "mysql password?" demo
	db_pass=$REPLY
	get_db_name

	# we are using "|" as delimiter for sed to avoid escaped sequences in $dt_dir
	sed -e "s|^\(\$config\['db'\]\['user'\]\).*|\1 = '$db_user';|" \
		-e "s|^\/*\(\$config\['db'\]\['password'\]\).*|\1 = '$db_pass';|" \
		-e "s|^\/*\(\$config\['db'\]\['dbname'\]\).*|\1 = '$db_name';|" \
		-e "s|^\/*\(\$config\['lib'\]\['doctrine'\]\).*|\1 = '$dt_dir';|" \
	< $vz_dir/etc/volkszaehler.conf.template.php \
	> $config
	
	pushd $vz_dir
	php misc/tools/doctrine orm:generate-proxies
	popd
fi

###############################
echo
ask "create database?" y
if [ "$REPLY" == "y" ]; then
	get_admin

	echo "creating database $db_name..."
	mysql -h$db_host -u$db_admin_user -p$db_admin_pass -e 'CREATE DATABASE `'$db_name'`'
	pushd $vz_dir
	php misc/tools/doctrine orm:schema-tool:create
	popd

	echo "creating db user $db_user with proper rights..."
	mysql -h$db_host -u$db_admin_user -p$db_admin_pass <<-EOF
		CREATE USER '$db_user'@'$db_host' IDENTIFIED BY '$db_pass';
		GRANT USAGE ON *.* TO '$db_user'@'$db_host';
		GRANT SELECT, UPDATE, INSERT ON $db_name.* TO '$db_user'@'$db_host';
	EOF
fi

echo
ask "insert demo data in to database?" n
if [ "$REPLY" == "y" ]; then
	get_admin
	get_db_name
	cat $vz_dir/misc/sql/demo/entities.sql $vz_dir/misc/sql/demo/properties.sql $vz_dir/misc/sql/demo/data-demoset1.sql |
	mysql -h$db_host -u$db_admin_user -p$db_admin_pass $db_name
fi

cleanup


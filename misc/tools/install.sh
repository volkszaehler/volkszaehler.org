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

# minimum php version required
php_ver_min=5.3

# git url
vz_git=https://github.com/volkszaehler/volkszaehler.org

# cannot handle other hosts right now
db_host=localhost


###############################
# functions
ask() {
	question="$1"
	default="$2"
	read -e -p "$question [$default] "
	REPLY="${REPLY:-$default}"
}

cleanup() {
	if [ -e "$tmp_dir" ]; then
		rm -r "$tmp_dir"
	fi
}

get_config() {
	test -n "$config" && return
	config="$vz_dir/etc/volkszaehler.conf.php"
	cp "$vz_dir/etc/volkszaehler.conf.template.php" "$config"
}

get_admin() {
	test -n "$db_admin_user" && return
	ask "mysql admin user?" root
	db_admin_user="$REPLY"
	ask "mysql admin password?"
	db_admin_pass="$REPLY"
	get_config
	sed -i \
		-e "s/^\/*\(\$config\['db'\]\['admin'\]\['password'\]\).*/\1 = '$db_admin_pass';/" \
		-e "s/^\/*\(\$config\['db'\]\['admin'\]\['user'\]\).*/\1 = '$db_admin_user';/" \
	"$config"
}

get_db_name() {
	test -n "$db_name" && return
	ask "mysql database?" volkszaehler
	db_name="$REPLY"
}

###############################
# header
echo "volkszaehler.org installation script"

###############################
# check prerequisites
echo
echo -n "checking prerequisites:"

deps=( php mysql awk sed grep wget mktemp mkdir git )
for binary in "${deps[@]}"; do
	binpath="$(which $binary)"
	if [ -n "$binpath" ]; then
		echo " $binary: $binpath"
	else
		echo
		echo " $binary: not found. Please install to use this script (e.g. sudo apt-get install $binary)."
		cleanup && exit 1
	fi
done
echo

# get a temp dir
tmp_dir=$(mktemp -d)

# check php version
php_version=$(php -r 'echo PHP_VERSION;')
echo -n "checking php version: $php_version "
# due to php magic, this also works with stuff like "5.3.3-7+squeeze19"
if php -r "exit(PHP_VERSION >= $php_ver_min ? 0 : 1);"; then
	echo ">= $php_ver_min, ok"
else
	echo "is too old, $php_ver_min or higher required"
	cleanup && exit 1
fi

###############################
echo
echo "volkszaehler setup..."

ask "volkszaehler path?" /var/www/volkszaehler.org
vz_dir="$REPLY"

if [ -e "$vz_dir" ]; then
	ask "$vz_dir already exists. overwrite?" n
else
	mkdir "$vz_dir"
	REPLY=y
fi

if [ "$REPLY" == 'y' ]; then
	echo "installing volkszaehler.org into $vz_dir"
	git clone "$vz_git" "$tmp_dir"
	cp -r "$tmp_dir"*/* "$vz_dir/"
fi

###############################
echo
echo "checking composer..."

for f in composer composer.phar; do
	COMPOSER=$(which $f 2>/dev/null || true)
	test -n "$COMPOSER" && break
done
if [ -n "$COMPOSER" ]; then
	echo "composer: $COMPOSER"
else
	pushd "$vz_dir"
		echo "composer not found, downloading..."
		php -r "eval('?>'.file_get_contents('https://getcomposer.org/installer'));"
		COMPOSER="$vz_dir/composer.phar"
	popd
fi

###############################
echo
echo "installing dependencies..."

pushd "$vz_dir"
"$COMPOSER" install --no-dev
popd

###############################
echo
ask "create database?" y

if [ "$REPLY" == "y" ]; then
	get_admin
	get_db_name

	echo "creating database $db_name..."
	mysql -h"$db_host" -u"$db_admin_user" -p"$db_admin_pass" -e 'CREATE DATABASE `'"$db_name"'`'
	pushd "$vz_dir"
	php misc/tools/doctrine orm:schema-tool:create
	popd

	echo "creating db user $db_user with proper rights..."
	mysql -h"$db_host" -u"$db_admin_user" -p"$db_admin_pass" <<-EOF
		CREATE USER '$db_user'@'$db_host' IDENTIFIED BY '$db_pass';
		GRANT USAGE ON *.* TO '$db_user'@'$db_host';
		GRANT SELECT, UPDATE, INSERT ON $db_name.* TO '$db_user'@'$db_host';
		GRANT DELETE ON $db_name.entities_in_aggregator TO '$db_user'@'$db_host';
		GRANT DELETE ON $db_name.properties TO '$db_user'@'$db_host';
	EOF
fi

###############################
echo
ask "configure volkszaehler.org?" y

if [ "$REPLY" == "y" ]; then
	# test for pdo_mysql php module
	php -m | grep pdo_mysql > /dev/null
	if [ $? -ne 0 ]; then
		echo "php module pdo_mysql has not been found"
		echo "try 'sudo apt-get install php5-mysql' on Debian/Ubuntu based systems"
		cleanup && exit 1
	fi

	ask "mysql user?" vz
	db_user="$REPLY"
	ask "mysql password?" demo
	db_pass="$REPLY"

	get_db_name
	get_config

	# we are using "|" as delimiter for sed to avoid escaped sequences in $dt_dir
	sed	-i \
		-e "s|^\(\$config\['db'\]\['user'\]\).*|\1 = '$db_user';|" \
		-e "s|^\/*\(\$config\['db'\]\['password'\]\).*|\1 = '$db_pass';|" \
		-e "s|^\/*\(\$config\['db'\]\['dbname'\]\).*|\1 = '$db_name';|" \
	"$config"

	pushd "$vz_dir"
	php misc/tools/doctrine orm:generate-proxies
	popd
fi

###############################
echo
ask "allow channel deletion?" n
if [ "$REPLY" == "y" ]; then
	get_admin
	get_db_name

	echo "adding db user $db_user delete rights..."
	mysql -h"$db_host" -u"$db_admin_user" -p"$db_admin_pass" <<-EOF
		GRANT DELETE ON $db_name.* TO '$db_user'@'$db_host';
	EOF
fi

echo
ask "insert demo data in to database?" n
if [ "$REPLY" == "y" ]; then
	get_admin
	get_db_name
	cat "$vz_dir/misc/sql/demo/entities.sql" "$vz_dir/misc/sql/demo/properties.sql" "$vz_dir/misc/sql/demo/data-demoset1.sql" |
		mysql -h"$db_host" -u"$db_admin_user" -p"$db_admin_pass" "$db_name"
fi

cleanup

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

# default vz dir (overriden by command line)
vz_dir=${1:-/var/www/volkszaehler.org}

###############################
# functions
ask() {
	question="$1"
	default="$2"
	read -e -p "$question [$default] "
	REPLY="${REPLY:-$default}"
}

cleanup() {
	# nothing to do yet
	:
}

get_db_admin() {
	test -n "$db_admin_user" && return
	ask "mysql admin user?" root
	db_admin_user="$REPLY"
	ask "mysql admin password?"
	db_admin_pass="$REPLY"
	sed -i \
		-e "s/^\/*\(\$config\['db'\]\['admin'\]\['password'\]\).*/\1 = '$db_admin_pass';/" \
		-e "s/^\/*\(\$config\['db'\]\['admin'\]\['user'\]\).*/\1 = '$db_admin_user';/" \
	"$config"
}

get_db_name() {
	test -n "$db_name" && return
	ask "mysql database?" volkszaehler
	db_name="$REPLY"
	sed	-i \
		-e "s|^\/*\(\$config\['db'\]\['dbname'\]\).*|\1 = '$db_name';|" \
	"$config"
}

get_db_user_pass() {
	test -n "$db_user" && return
	ask "mysql user?" vz
	db_user="$REPLY"
	ask "mysql password?" demo
	db_pass="$REPLY"
	# we are using "|" as delimiter for sed to avoid escaped sequences
	sed	-i \
		-e "s|^\(\$config\['db'\]\['user'\]\).*|\1 = '$db_user';|" \
		-e "s|^\/*\(\$config\['db'\]\['password'\]\).*|\1 = '$db_pass';|" \
	"$config"
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
	if binpath="$(which $binary)" ; then
		echo " $binary: $binpath"
	else
		echo
		echo " $binary: not found. Please install to use this script (e.g. sudo apt-get install $binary)."
		cleanup && exit 1
	fi
done
echo
if ! (php -m | grep -q pdo_mysql) ; then
	echo "php module pdo_mysql has not been found"
	echo "try 'sudo apt-get install php5-mysqlnd' on Debian/Ubuntu based systems"
	cleanup && exit 1
fi

# check php version
php_version=$(php -r 'echo PHP_VERSION;')
echo -n "checking php version: $php_version "
if php -r "exit(version_compare(PHP_VERSION, '$php_ver_min', '>=')? 0 : 1);"; then
	echo ">= $php_ver_min, ok"
else
	echo "is too old, $php_ver_min or higher required"
	cleanup && exit 1
fi

###############################
echo
echo "volkszaehler setup..."

ask "volkszaehler path?" "$vz_dir"
vz_dir="$REPLY"
config="$vz_dir/etc/volkszaehler.conf.php"

if [ -e "$vz_dir" ]; then
	ask "$vz_dir already exists. remove it and get new git clone? (you have to type 'Yes' to do this!)" n
	if [ "$REPLY" == 'Yes' ]; then
		rm -fr "$vz_dir"
		REPLY=y
	else
		REPLY=n
	fi
else
	REPLY=y
fi
if [ "$REPLY" == 'y' ]; then
	echo "git clone volkszaehler.org into $vz_dir"
	git clone "$vz_git" "$vz_dir"
fi

###############################
echo
echo "checking composer..."

COMPOSER="$vz_dir/composer.phar"
if [ ! -e "$COMPOSER" ]; then
	for f in composer composer.phar; do
		COMPOSER=$(which $f 2>/dev/null || true)
		test -n "$COMPOSER" && break
	done
fi
if [ ! -e "$COMPOSER" ]; then
	pushd "$vz_dir"
		echo "composer not found, downloading..."
		php -r "eval('?>'.file_get_contents('https://getcomposer.org/installer'));"
		COMPOSER="$vz_dir/composer.phar"
	popd
fi
echo "composer: $COMPOSER"

###############################
echo
echo "installing dependencies..."

pushd "$vz_dir"
"$COMPOSER" install --no-dev

echo
ask "install server-side graph generation (jpgraph, not required for frontend)?" n
if [ "$REPLY" == "y" ]; then
	"$COMPOSER" require jpgraph/jpgraph:dev-master
fi
popd

###############################
echo
if [ ! -e "$config" ]; then
	echo "volkszaehler.org is not configured yet. creating new config from sample config file."
	cp "$vz_dir/etc/volkszaehler.conf.template.php" "$config"
	REPLY=y
else
	ask "configure volkszaehler.org database access?" y
fi
if [ "$REPLY" == "y" ]; then
	get_db_admin
	get_db_name
	get_db_user_pass
fi

###############################
echo
ask "create volkszaehler.org database?" y
if [ "$REPLY" == "y" ]; then
	echo "creating database $db_name..."
	mysql -h"$db_host" -u"$db_admin_user" -p"$db_admin_pass" -e 'CREATE DATABASE `'"$db_name"'`'
	pushd "$vz_dir"
	php misc/tools/doctrine orm:schema-tool:create
	php misc/tools/doctrine orm:generate-proxies
	popd
fi

###############################
echo
ask "create volkszaehler.org database user?" y
if [ "$REPLY" == "y" ]; then
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
ask "allow channel deletion?" n
if [ "$REPLY" == "y" ]; then
	get_db_admin
	get_db_name

	echo "adding db user $db_user delete rights..."
	mysql -h"$db_host" -u"$db_admin_user" -p"$db_admin_pass" <<-EOF
		GRANT DELETE ON $db_name.* TO '$db_user'@'$db_host';
	EOF
fi

echo
ask "insert demo data in to database?" n
if [ "$REPLY" == "y" ]; then
	get_db_admin
	get_db_name
	cat "$vz_dir/misc/sql/demo/entities.sql" "$vz_dir/misc/sql/demo/properties.sql" "$vz_dir/misc/sql/demo/data-demoset1.sql" |
		mysql -h"$db_host" -u"$db_admin_user" -p"$db_admin_pass" "$db_name"
fi

cleanup


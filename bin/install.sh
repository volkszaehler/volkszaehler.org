#!/bin/bash
#
# Installer
#
# For creating/updating the configuration/database
# and downloading of required libraries
# and configuration of of the PHP interpreter/webserver
#
# @author Jakob Hirsch
# @copyright Copyright (c) 2011-2020, The volkszaehler.org project
# @license https://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License version 3
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
php_ver_min=7.1

# git url
vz_git=https://github.com/volkszaehler/volkszaehler.org

# cannot handle other hosts right now
db_host=localhost

# default vz dir (overriden by command line)
vz_dir=${1:-~/volkszaehler.org}

# default webserver dir (overriden by command line)
web_dir=${1:-/var/www/volkszaehler.org}


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

get_db_root() {
	test -n "$db_root_user" && return
	ask "mysql root user?" root
	db_root_user="$REPLY"
	ask "mysql root password?"
	db_root_pass="$REPLY"
}

get_db_name() {
	test -n "$db_name" && return
	ask "mysql database?" volkszaehler
	db_name="$REPLY"
	sed -i.bak \
		-e "/db:/,/admin:/ s/\(\s*\)dbname:.*/\1dbname: $db_name/" \
	"$config"
}

get_db_admin_pass() {
	test -n "$db_admin_user" && return
	ask "mysql admin to $db_name database?" vz-admin
	db_admin_user="$REPLY"
	ask "mysql admin password?"
	db_admin_pass="$REPLY"
	# note: use sed ranges to limit to admin section
	sed -i.bak \
		-e "/admin:/,/push:/ s/\(\s*\)user:.*/\1user: $db_admin_user/" \
		-e "/admin:/,/push:/ s/\(\s*\)password:.*/\1password: $db_admin_pass/" \
	"$config"
}

get_db_user_pass() {
	test -n "$db_user" && return
	ask "mysql user?" vz
	db_user="$REPLY"
	ask "mysql password?" demo
	db_pass="$REPLY"
	# note: use sed ranges to limit to db section
	sed -i.bak \
		-e "/db:/,/admin:/ s/\(\s*\)user:.*/\1user: $db_user/" \
		-e "/db:/,/admin:/ s/\(\s*\)password:.*/\1password: $db_pass/" \
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
if ! (php -m | grep -q mysql) ; then
	echo "php module for mysql has not been found"
	echo "try 'sudo apt-get install php-mysql' on Debian/Ubuntu based systems"
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

if [ -e './etc/config.dist.yaml' ]; then
	vz_dir="."

	ask "volkszaehler.org already exists in the current directory. Update git repository?" y
	if [ "$REPLY" == 'y' ]; then
		git pull
	fi
else
	ask "volkszaehler path?" "$vz_dir"
	vz_dir="$REPLY"

	if [ -e "$vz_dir" ]; then
		ask "$vz_dir already exists. Remove it and get new git clone? (you have to type 'Yes' to do this!)" n
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

	ask "link from webserver to volkszaehler directory?" "$web_dir"
	web_dir="$REPLY"

	#check if symbolic link from "web_dir" already exists
	if [ -h "$web_dir" ]; then
		ask "$web_dir symlink already exists. Remove it? (you have to type 'Yes' to do this!)" n
		if [ "$REPLY" == 'Yes' ]; then
			sudo rm -fr "$web_dir"
			REPLY=y
		else
			REPLY=n
		fi
	#check if "web_dir" is already a directory
	elif [ -d "$web_dir" ]; then
		ask "$web_dir directory already exists. Remove it? (this will remove a previous installation and all changes you made - type 'Yes' to do this!)" n
		if [ "$REPLY" == 'Yes' ]; then
			sudo rm -fr "$web_dir"
			REPLY=y
		else
			REPLY=n
		fi
	#check if parent directory of "web_dir" exists
	elif [ ! -d "${web_dir%/*}" ]; then
		ask "parent directory ${web_dir%/*} doesn't exist, create it?" y
		if [ "$REPLY" == 'y' ]; then
			sudo mkdir ${web_dir%/*}
			REPLY=y
		else
			#parent directory doesn't exist, so create symbolic link will fail.
			echo "parent directory ${web_dir%/*} doesn't exist, so creating symbolic link from $web_dir will fail."
			cleanup && exit 1            
		fi
	else
		REPLY=y
	fi

	if [ "$REPLY" == 'y' ]; then
		echo "linking $web_dir to $vz_dir"
		sudo ln -sf "$vz_dir" "$web_dir"
	fi

fi

config="$vz_dir/etc/config.yaml"

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
	if [ -e "composer.lock" ]; then
		"$COMPOSER" update
	else
		"$COMPOSER" install --no-dev
	fi
popd

###############################
echo
if [ ! -e "$config" ]; then
	echo "volkszaehler.org is not configured yet. creating new config from sample config file."
	cp "$vz_dir/etc/config.dist.yaml" "$config"
	REPLY=y
else
	ask "configure volkszaehler.org database access?" y
fi
if [ "$REPLY" == "y" ]; then
	get_db_root
	get_db_name
	get_db_admin_pass
	get_db_user_pass
fi

###############################
echo
ask "create volkszaehler.org database and admin user?" y
if [ "$REPLY" == "y" ]; then
	get_db_root
	get_db_name
	get_db_admin_pass

	echo "creating database $db_name..."
	sudo mysql -h"$db_host" -u"$db_root_user" -p"$db_root_pass" -e 'CREATE DATABASE `'"$db_name"'`'
	echo "creating db user $db_admin_user..."
	sudo mysql -h"$db_host" -u"$db_root_user" -p"$db_root_pass"  <<-EOF
		GRANT ALL ON $db_name.* to '$db_admin_user'@'$db_host' IDENTIFIED BY '$db_admin_pass' WITH GRANT OPTION;
		EOF
	echo "creating database schema..."
	pushd "$vz_dir"
		php bin/doctrine orm:schema-tool:create
		php bin/doctrine orm:generate-proxies
	popd
fi

###############################
echo
ask "create volkszaehler.org database user?" y
if [ "$REPLY" == "y" ]; then
	get_db_root
	get_db_name
	get_db_user_pass

	echo "creating db user $db_user with proper rights..."
	sudo mysql -h"$db_host" -u"$db_root_user" -p"$db_root_pass" <<-EOF
		CREATE USER '$db_user'@'$db_host' IDENTIFIED BY '$db_pass';
		GRANT USAGE ON $db_name.* TO '$db_user'@'$db_host';
		GRANT SELECT, UPDATE, INSERT ON $db_name.* TO '$db_user'@'$db_host';
		GRANT DELETE ON $db_name.entities_in_aggregator TO '$db_user'@'$db_host';
		GRANT DELETE ON $db_name.properties TO '$db_user'@'$db_host';
		GRANT DELETE ON $db_name.aggregate TO '$db_user'@'$db_host';
	EOF
fi

###############################
echo
ask "allow channel deletion?" n
if [ "$REPLY" == "y" ]; then
	get_db_admin_pass
	get_db_name

	echo "granting db user $db_user delete rights..."
	mysql -h"$db_host" -u"$db_admin_user" -p"$db_admin_pass" <<-EOF
		GRANT DELETE ON $db_name.* TO '$db_user'@'$db_host';
	EOF
fi

echo
ask "insert demo data in to database?" n
if [ "$REPLY" == "y" ]; then
	get_db_admin_pass
	get_db_name

	cat "$vz_dir/misc/sql/demo.sql" |
		mysql -h"$db_host" -u"$db_admin_user" -p"$db_admin_pass" "$db_name"
fi

cleanup

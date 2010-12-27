#!/bin/bash
# Jakob Hirsch, 2010-11-20
#

# cannot handle other hosts right now
db_host=localhost 

set -e
shopt -s nocasematch

doctrine_git=git://github.com/doctrine/doctrine2.git
vz_git=git://github.com/volkszaehler/volkszaehler.org.git

ask() {
	question=$1
	default=$2
	read -e -p "$question [$default] "
	REPLY=${REPLY:-$default}
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

############
echo
echo doctrine setup...

ask "doctrine path?" /usr/local/lib/volkszaehler.org/doctrine
dtdir=$REPLY

REPLY=y
test -e "$dtdir" && ask "$dtdir already exists. overwrite?" n
if [ "$REPLY" == 'y' ]; then
	echo "installing doctrine into $dtdir"
	mkdir -p $dtdir
	git clone $doctrine_git $dtdir
	pushd $dtdir
	git submodule init
	git submodule update

	cd lib/Doctrine
	ln -s ../vendor/doctrine-dbal/lib/Doctrine/DBAL/ .
	ln -s ../vendor/doctrine-common/lib/Doctrine/Common/ .
	popd
fi

############
echo
echo volkszaehler setup...

#/var/www/volkszaehler.org
ask "volkszaehler path?" /var/www/vz
vzdir=$REPLY

REPLY=y
test -e "$vzdir" && ask "$vzdir already exists. overwrite?" n
if [ "$REPLY" == 'y' ]; then
	echo "installing volkszaehler.org into $vzdir"
	mkdir -p $vzdir
	git clone $vz_git $vzdir

	pushd $vzdir/lib/vendor
	ln -s $dtdir/lib/Doctrine/ .
	ln -s $dtdir/lib/vendor/Symfony/ .
	popd
fi

config=$vzdir/etc/volkszaehler.conf.php

############
echo
ask "configure volkszaehler.org (database connection)?" y

if [ "$REPLY" == "y" ]; then
	echo database config...
	ask "mysql user?" vz
	db_user=$REPLY
	ask "mysql password?" demo
	db_pass=$REPLY
	get_db_name

	sed -e "s/^\(\$config\['db'\]\['user'\]\).*/\1 = '$db_user';/" \
		-e "s/^\(\$config\['db'\]\['password'\]\).*/\1 = '$db_pass';/" \
		-e "s/^\(\$config\['db'\]\['dbname'\]\).*/\1 = '$db_name';/" \
	< $vzdir/etc/volkszaehler.conf.template.php \
	> $config
fi

#########
echo
ask "create database?" y
if [ "$REPLY" == "y" ]; then
	get_admin

	echo creating database $db_name...
	mysql -h$db_host -u$db_admin_user -p$db_admin_pass -e 'CREATE DATABASE `'$db_name'`'
	pushd $vzdir
	php $dtdir/doctrine orm:schema-tool:create
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
	cat $vzdir/share/sql/demo/entities.sql $vzdir/share/sql/demo/properties.sql $vzdir/share/sql/demo/data-demoset1.sql |
	mysql -h$db_host -u$db_admin_user -p$db_admin_pass $db_name
fi


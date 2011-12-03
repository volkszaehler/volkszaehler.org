#!/bin/bash
useradd -r -c "Messbus Cron User" -d /var/lib/mbus -m -G dialout mbus

mkdir -p /etc/mbus /usr/local/lib/site_perl

cp mbus.pm /usr/local/lib/site_perl/

cp mbus-cmd /usr/local/bin/

chmod 755 /usr/local/bin/mbus-cmd

cp mbusconf.pm /etc/mbus

chmod 644 /etc/mbus/mbusconf.pm

touch /var/lib/mbus/vzold



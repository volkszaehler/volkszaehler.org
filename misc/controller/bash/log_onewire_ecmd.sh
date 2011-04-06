#!/bin/bash
#
# This is a simple bash script to read Dallas 1-Wire sensors
# connected to a controller board running ethersex and log their 
# values to the volkszaehler project.
#
# call it with a cronjob similiar to this one:
#
# */5 * * * *   ~/bin/log_onewire_ecmd.sh
#
# @copyright Copyright (c) 2011, The volkszaehler.org project
# @package controller
# @license http://www.opensource.org/licenses/gpl-license.php GNU Public License
# @author Justin Otherguy <justin@justinotherguy.org>
# @author Steffen Vogel <info@steffenvogel.de>
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

# configuration
#
# middleware url
URL="http://volkszaehler.org/demo/middleware.php"

# sensor settings
#  id of the sensor
SENSORID=<put your onewire sensors hw id here>

#  ip address of the controller board running ethersex
ESEXIP=<put the ip address of your controller board here>

#  uuid of the sensor in the volkszaehler database
UUID=<put the uuid of your temperature sensor here>

## 
# paths to binaries - you should not need to change these
CURL=/usr/bin/curl
NC=/bin/nc


# ========= do not change anything below this line ==============

echo "1w convert $SENSORID" |$NC $ESEXIP 2701 -q 1 2>/dev/null | grep -qe OK || exit 1
TEMPERATURE=`echo 1w get $SENSORID | $NC $ESEXIP 2701 -q 1 2>/dev/null | sed -e 's/Temperatur: //'`

$CURL --data "" "$URL/data/$UUID.json?value=$TEMPERATURE"

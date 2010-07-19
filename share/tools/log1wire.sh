#!/bin/bash
#
# Copyright (c) 2010 by Justin Otherguy <justin@justinotherguy.org>
#
# This program is free software; you can redistribute it and/or modify it
# under the terms of the GNU General Public License (either version 2 or
# version 3) as published by the Free Software Foundation.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
#
# For more information on the GPL, please go to:
# http://www.gnu.org/copyleft/gpl.html
#
# This is a simple bash script to read Dallas 1-Wire sensors
# with digitemp and log their values to the volkszaehler project.
#
# call it with a cronjob similiar to this one:
#
# */5 * * * *	~/bin/log1wire.sh
#

# configuration
#
# backend url
URL="http://localhost/workspace/volkszaehler.org/backend/index.php"

# 1wire sensor id => volkszaehler.org ucid
declare -A MAPPING
MAPPING["1012E6D300080077"]="93f85330-9037-11df-86d3-379c018a387b"

# the digitemp binary, choose the right one for your adaptor
DIGITEMP="digitemp_DS9097"

# the digitemp configuration (holds your sensor ids)
DIGITEMP_CONF="/home/steffen/.digitemprc"

# the port of your digitemp adaptor
DIGITEMP_PORT="/dev/ttyUSB0"

# additional options for digitemp
# specify single or all sensors here for example
DIGITEMP_OPTS="-t 0"
#DIGITEMP_OPTS="-a"

# additional options for curl
# specify credentials, proxy etc here
CURL_OPTS=""

# enable this for a more verbose output
#DEBUG=true

# ========================= do not change anything under this line

# building digitemp options
DIGITEMP_OPTS="-c ${DIGITEMP_CONF} ${DIGITEMP_OPTS} -s ${DIGITEMP_PORT} -q -o %s;%R;%N;%C"

if [ $DEBUG ]; then
	echo "enabling debugging output"
	echo -e "running digitemp:\t${DIGITEMP} ${DIGITEMP_OPTS}"
fi

# execute digitemp
LINES=$(${DIGITEMP} ${DIGITEMP_OPTS})

# save old internal field seperator
OLD_IFS=${IFS}
IFS=$'\n'

for LINE in $LINES
do
	IFS=";"
	COLUMNS=( $LINE )
	IFS=${OLD_IFS}

	if [ -z ${MAPPING[${COLUMNS[1]}]} ]; then
		echo "sensor ${COLUMNS[1]} is not mapped to an ucid" >&2
		echo "please add it to the script. Example:" >&2
		echo >&2
		echo -e "MAPPING[\"${COLUMNS[1]}\"]=\"9aa643b0-9025-11df-9b68-8528e3b655ed\"" >&2
	elif [ ${COLUMNS[3]:0:2} == "85" ]; then
		echo "check your wiring; we received an invalid reading!" 1>2&
	else
		UCID=${MAPPING[${COLUMNS[1]}]}
		REQUEST_URL="${URL}?format=json&controller=data&action=add&ucid=${UCID}&value=${COLUMNS[3]}&timestamp=$(( ${COLUMNS[2]} * 1000 ))${URL_PARAMS}${DEBUG:+&debug=1}"

		if [ $DEBUG ]; then
			echo -e "logging sensor:\t\t${UCID}"
			echo -e "with value:\t\t${COLUMNS[3]}"
			echo -e "at\t\t\t$(date -d @${COLUMNS[2]})"
			echo "|"
		fi

		curl ${CURL_OPTS} ${DEBUG:-"-s"} ${DEBUG:-"-o /dev/null"} ${DEBUG:+"--verbose"} "${REQUEST_URL}" 2>&1 | sed 's/^/|\t/'
	fi
done

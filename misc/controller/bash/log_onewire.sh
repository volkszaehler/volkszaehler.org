#!/bin/bash
#
# This is a simple bash script to read Dallas 1-Wire sensors
# with digitemp and log their values to the volkszaehler project.
#
# call it with a cronjob similiar to this one:
#
# */5 * * * *   ~/bin/log_onewire.sh
#
# @copyright Copyright (c) 2010, The volkszaehler.org project
# @package controller
# @license http://www.opensource.org/licenses/gpl-license.php GNU Public License
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

# 1wire sensor id => volkszaehler.org uuid
declare -A MAPPING
MAPPING["1012E6D300080077"]="9eed00f0-ca37-11df-9d39-15423b3b842b"
MAPPING["10E3D2C400080017"]="875d2cc0-da4b-11df-a67f-e9bb235c3849"
MAPPING["10F59F84010800B0"]="4b549c20-da4f-11df-bd60-4b520f9cd4e0"
MAPPING["1060BB840108000D"]="5fcc9b40-da4f-11df-b981-d55799876663"

# the digitemp binary, choose the right one for your adapter
DIGITEMP="digitemp_DS9097"

# the digitemp configuration (holds your sensor ids)
DIGITEMP_CONF="/home/steffen/.digitemprc"

# additional options for digitemp
# specify single or all sensors here for example
#DIGITEMP_OPTS="-t 0"
DIGITEMP_OPTS="-a"

# additional options for curl
# specify credentials, proxy etc here
CURL_OPTS=""

# uncomment this for a more verbose output
#DEBUG=1

# ========= do not change anything under this line ==============

# building digitemp options
DIGITEMP_OPTS="-c ${DIGITEMP_CONF} ${DIGITEMP_OPTS} -q -o %s;%R;%N;%C"

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
	OLD_IFS=${IFS}
	IFS=";"
	COLUMNS=( $LINE )
	IFS=${OLD_IFS}

	if [ ${COLUMNS[3]:0:2} == "85" ]; then
		echo "check your wiring; we received an invalid reading!" >&2
	elif [[ ${MAPPING[${COLUMNS[1]}]} ]]; then
		UUID=${MAPPING[${COLUMNS[1]}]}
		REQUEST_URL="${URL}/data/${UUID}.json?value=${COLUMNS[3]}&timestamp=$(( ${COLUMNS[2]} * 1000 ))${URL_PARAMS}${DEBUG:+&debug=1}"

		if [ $DEBUG ]; then
			echo -e "logging sensor:\t\t${UUID}"
			echo -e "with value:\t\t${COLUMNS[3]}"
			echo -e "at:\t\t\t$(date -d @${COLUMNS[2]})"
			echo -e "with request:\t\t${REQUEST_URL}"
		fi

		curl ${CURL_OPTS} --data "" "${REQUEST_URL}"
	# prohibit unmapped sensors
	else
		echo "sensor ${COLUMNS[1]} is not mapped to an uuid! add the mapping in the script." >&2
	fi

done

IFS=${OLD_IFS}

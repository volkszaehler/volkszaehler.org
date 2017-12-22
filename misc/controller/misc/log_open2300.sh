#!/bin/bash
#
# This is a simple bash script to read Ws2300 weatherstation
# with open2300 and log their values to the volkszaehler project.
#
# call it with a cronjob similiar to this one:
#
# */5 * * * *   ~/bin/log_open2300.sh
#
# @author Steffen Vogel <info@steffenvogel.de>
# modifications from the original file log_onewire.sh
# @author Berthold Bredenkamp
# @copyright Copyright (c) 2011-2017, The volkszaehler.org project
# @license http://www.gnu.org/licenses/gpl.txt GNU Public License
#
#
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
URL="http://192.168.xx.xx/volkszaehler.org/htdocs/middleware.php"

# open2300 sensor id => volkszaehler.org uuid
declare -A MAPPING
MAPPING["Ti"]="4a6f0590-1486-11e1-9f7d-df4a9a176axx"
MAPPING["To"]="962f2410-1486-11e1-9072-eb862e84b7xx"
MAPPING["RHi"]="5f130f50-1531-11e1-a6f4-b9ae48246exx"
MAPPING["RHo"]="4e32a540-1531-11e1-90bc-f396172e4exx"
MAPPING["RP"]="0e582aa0-1531-11e1-97ce-3df5752d26xx"

# from the open2300 binarys
DIGITEMP="/usr/local/bin/fetch2300"

# additional options for curl
# specify credentials, proxy etc here
CURL_OPTS=""

# uncomment this for a more verbose output
#DEBUG=1

# ========= do not change anything under this line ==============

if [ $DEBUG ]; then
	echo "enabling debugging output"
	echo -e "running fetch2300:\t${DIGITEMP} ${DIGITEMP_OPTS}"
fi

# execute digitemp
# LINES=$(${DIGITEMP} ${DIGITEMP_OPTS})
LINES=$(${DIGITEMP} )

# save old internal field seperator
OLD_IFS=${IFS}
IFS=$'\n'

#echo "Test1"
ZEIT=`date +%s`

for LINE in $LINES
do
#	echo $LINE
	OLD_IFS=${IFS}
	IFS=$' '
	COLUMNS=( $LINE )
#
	INDEX=${COLUMNS[0]}
	WERT=${COLUMNS[1]}
#	echo "Zeile: ${COLUMNS[0]} Wert: ${WERT}"
	IFS=${OLD_IFS}

	if [[ ${MAPPING[${COLUMNS[0]}]} ]]; then
		UUID=${MAPPING[${COLUMNS[0]}]}
	 	REQUEST_URL="${URL}/data/${UUID}.json?value=${COLUMNS[1]}&timestamp=$(( ${ZEIT} * 1000 ))${URL_PARAMS}${DEBUG:+&debug=1}"

		if [ $DEBUG ]; then
			echo -e "logging sensor:\t\t${UUID}"
			echo -e "with value:\t\t${COLUMNS[3]}"
			echo -e "at:\t\t\t$(date -d @${COLUMNS[2]})"
			echo -e "with request:\t\t${REQUEST_URL}"
		fi

		curl ${CURL_OPTS} --data "" "${REQUEST_URL}"
	# prohibit unmapped sensors
	else
		if [ $DEBUG ]; then
			echo "sensor ${COLUMNS[1]} is not mapped to an uuid! add the mapping in the script." >&2
		fi
	fi

done

IFS=${OLD_IFS}

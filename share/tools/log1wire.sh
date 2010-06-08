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
URL="http://localhost/workspace/volkszaehler.org/content/backend.php"
# URL_PARAMS="&debug=1"

DIGITEMP="digitemp_DS9097"
DIGITEMP_OPTS="-a"
# DIGITEMP_OPTS="-t 0"
DIGITEMP_CONF="/home/steffen/.digitemprc"

CURL_OPTS=""

# ========================= do not change anything under this line

# special ucid prefix for 1wire sensors
UCID_PREFIX="07506920-6e7a-11df-"

# execute digitemp
LINES=$(${DIGITEMP} -c ${DIGITEMP_CONF} ${DIGITEMP_OPTS} -q -o "%N;%R;%C")

# save old internal field seperator
OLD_IFS=${IFS}
IFS=$'\n'

for LINE in $LINES
do
	IFS=";"
	COLUMNS=( $LINE )
	UCID="${UCID_PREFIX}${COLUMNS[1]:0:4}-${COLUMNS[1]:5}"
	curl ${CURL_OPTS} "${URL}?controller=data&action=add&ucid=${UCID}&value=${COLUMNS[2]}&timestamp=$(( ${COLUMNS[0]} * 1000 ))${URL_PARAMS}"
done

# reset old ifs
IFS=${OLD_IFS}

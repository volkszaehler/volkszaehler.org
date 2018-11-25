#!/bin/bash
#
# This is a simple bash script for reading digitalSTROM meters 
# connected to digitalSTROM server, and logs their values for the 
# project volkszähler. The meter reading is queried, so it is 
# relatively unimportant how often it is queried.
# 
# Start it with a cronjob similar to this one:
# * * * * * sudo /<pfad>/dsm.sh >> /var/log/dsm.log
#
# @author Thomas Hoepfner
# 


# configuration
#
# jsonclient to disassemble
jc=jsonclient

# vzclient to save
# make sure vzclient.conf has middleware url configured or add below
vzc=vzclient

# digitalSTROM server
# api=https://<your server>:8080/json
api=https://<your server>:8080/json

# Authentication token see chapter 6 in:
# http://developer.digitalstrom.org/Architecture/system-interfaces.pdf
app_token=<personal token>


# Configuration of the actual counters according to the format 
# 			[uuid]=dSMeter 
# The <uuid> is created in the middleware as 
# "El. energy (counter readings)", resolution 1000.
# The <dSmeter> is easily determined from the json response. 
# Remove the comment character in the line: 
# cat "$tmp_file" > #/var/www/volkszaehler.org/htdocs/dsm.json (after get data)
# Run the script once. In dsm.json are all digitalSTROM meters with description.
# The digit after level "dSmeters:" is the corresponding dSM.
#
declare -A sensors
sensors=(
            [11111111-1111-1111-1111-111111111111]=0
            [22222222-2222-2222-2222-222222222222]=1
)

# loginto digitalSTROM server
token=$("$jc" --url "$api/system/loginApplication?loginToken=$app_token" -e result,token)
echo "Security token: $token"

# temp file for data
tmp_file=$(mktemp)

# get data - dsm power,consumption
"$jc" --url "$api/property/query?query=/apartment/dSMeters/*(dSUID,name,powerConsumption,energyMeterValue)&token=$token" > "$tmp_file"


# analyze json response
#
#cat "$tmp_file" > /var/www/volkszaehler.org/htdocs/dsm.json
#exit  # Currency of the tests should be aborted here

# parse data

    for uuid in "${!sensors[@]}"; do
            sensor=${sensors[$uuid]}
            
            val=$("$jc" --file "$tmp_file" -e result,dSMeters,$sensor,energyMeterValue)
			echo $uuid  $val
            #if [ -n "$val" ]; then "$vzc" -u $uuid add data value=$val; fi
    done

rm "$tmp_file"

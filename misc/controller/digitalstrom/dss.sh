#!/bin/bash
#
# This script can be used to query digitalStrom sensors and post sensor values to volkszaehler
#
# make sure vzclient.conf has middleware url configured or add below
#

# definitions
jc=../../tools/jsonclient
vzc=../../tools/vzclient
api=https://digitalstromserver:8080/json
app_token=<your_registered_application_token>
uuid=<any_uuid>

# login
token=$("$jc" --url "$api/system/loginApplication?loginToken=$app_token" -e result,token)
echo "Security token: $token"

# temp file for data
tmp_file=$(mktemp)

# get data - all sensors/ all zones
"$jc" --url "$api/property/query?query=/apartment/zones/*(ZoneID,name)/devices/*(name,HWInfo,present)/sensorInputs/*(type,value)&token=$token" > "$tmp_file"

# parse data
#   repeat any number of times for each sensor
#   in this example, we'll read all EnOcean Temperature sensors
value=$("$jc" --file "$tmp_file" -e 'result,zones,@name=,0,devices,@HWInfo=EnOcean GmbH EnOcean Temperature sensor (A5-02-05),@name=SensorName,0,sensorInputs,0,value')

echo "$value"

"$vzc" -u $uuid add data value=$value

# to loop over multiple sensors consider something like the following (remove if false condition)
if false; then
    declare -A sensors
    
    sensors=(
            [11111111-1111-1111-1111-111111111111]=Sensor1Name
            [22222222-2222-2222-2222-222222222222]=Sensor2Name
    )

    for uuid in "${!sensors[@]}"; do
            sensor=${sensors[$uuid]}
            echo -n "$uuid $sensor "

            val=$("$jc" --file "$tmp_file" -e result,zones,@name=,0,devices,@name=$sensor,0,sensorInputs,0,value)
            echo $val
            if [ -n "$val" ]; then "$vzc" -u $uuid add data value=$val; fi
    done
fi

rm "$tmp_file"

#!/bin/bash 
## @author Robin Schneider <ypid23@aol.de>
## Cron: 39  *	* * *		~/log_weather.sh >> log.weather
## Find a weather station: http://weather.noaa.gov/
weather_id=EDDB
## middleware url
URL="http://localhost/volkszaehler/middleware.php"

##  uuid of the sensor in the volkszaehler database
UUID_temperature=''
UUID_pressure=''
UUID_relative_humidity=''

## 
## paths to binaries - you should not need to change these
CURL=/usr/bin/curl
NC=/bin/nc
dateH=`date +%H`
timestamp="`date -d "$dateH:20:00" "+%s000"`"

weatherfile="/tmp/weather.$weather_id"
weather -v -i $weather_id > "$weatherfile"
test -s "$weatherfile" || exit 1 ## file is empty
temperature="`grep 'Temperature' "$weatherfile"|sed -e 's/.*Temperature.*(\(.*\) C)/\1/'`"
pressure="`grep 'Pressure (altimeter)' "$weatherfile"|sed -e 's/.*(\(.*\) hPa).*/\1/'`"
relative_humidity="`grep 'Relative Humidity' "$weatherfile"|sed -e 's/.*Relative Humidity: \(.*\)%.*/\1/'`"

### Debug
if [ `date -u +%H` != `grep 'UTC' "$weatherfile"|sed -e 's/.*\([0-2][0-9]\)20 UTC/\1/'` ]
then	echo "`date -u +%H` is not equal with `grep 'UTC' "$weatherfile"|sed -e 's/.*\([0-2][0-9]\)20 UTC/\1/'`"
	exit 1
fi
# echo -e "**** "`date`"\t"`date -u`
# grep 'UTC' "$weatherfile"
# echo $temperature $relative_humidity $pressure
# echo $timestamp
### Debug

$CURL --data "" "$URL/data/$UUID_temperature.json?ts=$timestamp&value=$temperature" &>/dev/null
$CURL --data "" "$URL/data/$UUID_pressure.json?ts=$timestamp&value=$pressure" &>/dev/null
$CURL --data "" "$URL/data/$UUID_relative_humidity.json?ts=$timestamp&value=$relative_humidity" &>/dev/null

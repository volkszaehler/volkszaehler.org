#!/bin/bash 
## @author Robin Schneider <ypid23@aol.de>
## @outhor Norbert Walter <norbert-walter@web.de>
## Cron: 39  *	* * *		~/log_weather.sh >> log.weather
## This is a service from OpenWeatherMap.org
## Please install on Ubuntu the package curl, weather-util before using the script
## This script based on Robin Schneider and was modifyed from Norbert Walter
## because the old weather is out of service from NOAA and changed to OpenWeatherMap

## Location name by real name
CITY='Berlin'
## Unit metric for °C or imperial for K
UNIT='metric'
## OpenWeatherMap Application Key
## Create a free user account on openweathermap.org and use the free application key
APPID='jks234ksjd43kjh3k45h3k4h3k4hk3hj5k'
## OpenWeatherMap url
## http://api.openweathermap.org/data/2.5/weather?q=Berlin&mode=xml&units=metric&APPID=jks234ksjd43kjh3k45h3k4h3k4hk3hj5k
OWM="http://api.openweathermap.org/data/2.5/weather?q=$CITY&mode=xml&units=$UNIT&APPID=$APPID"
## middleware url
## http://yourserverip/volkszaehler.org/htdocs/middleware
URL="http://127.0.0.1/volkszaehler.org/htdocs/middleware"

##  uuid of the sensor in the volkszaehler database
UUID_temperature='12345678-1234-1234-1234-1234567890as'
UUID_pressure='12345678-1234-1234-1234-1234567890ad'
UUID_relative_humidity='12345678-1234-1234-1234-1234567890ah'

## 
## paths to binaries - you should not need to change these
CURL=/usr/bin/curl
NC=/bin/nc

weatherfile="/tmp/weather.$CITY"
## Get data from OpenWeatherMap
$CURL --data "" "$OWM" > "$weatherfile"
test -s "$weatherfile" || exit 1 ## file is empty
## Grep the weather data from answare
## $ echo "foo start blah blah blah stop bar" | sed 's/.*start \(.*\) stop.*/\1/'
## result: blah blah blah
temperature="`grep 'temperature value=' "$weatherfile"|sed -e 's/.*value="\(.*\)" min.*/\1/'`"
pressure="`grep 'pressure value=' "$weatherfile"|sed -e 's/.*value="\(.*\)" unit="hPa".*/\1/'`"
relative_humidity="`grep 'humidity value=' "$weatherfile"|sed -e 's/.*value="\(.*\)" unit="%".*/\1/'`"
last_update="`grep 'lastupdate value=' "$weatherfile"|sed -e 's/.*T\(.*\)".*/\1/'`"

## Convert the actual computer time in a timestamp
dateA=`date +%Y-%m-%d`
timeA=`date +%H:%M:%S`
## Timestamp actual computer time
## timestamp="`date -d "$timeA" "+%s000"`"
## Timestamp from OpenWeatherMap
timestamp="`date -d "$last_update" "+%s000"`"

### Debug
 echo -e "LocalTime: "`date`
 echo -e "UTC Time: "`date -u`
 echo "City: "$CITY
 echo "Temp: "$temperature"°C"
 echo "Humi: "$relative_humidity"%"
 echo "Pres: "$pressure"hPa"
 echo "Date: "$dateA $timeA
 echo "Last Update: "$last_update
 echo "Timestamp: "$timestamp
### Debug

# With timestemp from weather service
# $CURL --data "" "$URL/data/$UUID_temperature.json?ts=$timestamp&value=$temperature" &>/dev/null
# $CURL --data "" "$URL/data/$UUID_pressure.json?ts=$timestamp&value=$pressure" &>/dev/null
# $CURL --data "" "$URL/data/$UUID_relative_humidity.json?ts=$timestamp&value=$relative_humidity" &>/dev/null

# Withou timestamp, using local PC time
$CURL --data "" "$URL/data/$UUID_temperature.json?value=$temperature" &>/dev/null
$CURL --data "" "$URL/data/$UUID_pressure.json?value=$pressure" &>/dev/null
$CURL --data "" "$URL/data/$UUID_relative_humidity.json?value=$relative_humidity" &>/dev/null

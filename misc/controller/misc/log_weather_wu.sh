#!/bin/bash 
## @outhor Norbert Walter <norbert-walter@web.de>
## Cron: 39  *	* * *		~/log_weather_wu.sh >> log.weather_wu
## This is a service from www.wunderground.com
## Please install on Ubuntu the package curl before using the script
## This script get accurate weather data directly from personal weather stations
## in your environment. Word wide are more than 200.000 stations active.
## Info: Not all stations send all weather data. Please check the station on
## the webpage before using.


## Personal weater station location identifyer 
PWS='ISACHSEN356'
## WeatherUnderground Application Key
## Create a free user account on www.wunderground.com and use the free application key
## Limitations! max 500 requests per day and max 10 reqiests per minute
## Save interval must grater than 5 min otherwise stop the service!
APPID='1234567890123456'
## Personal weater station location name
## The station name you find on the webpage www.wunderground.com
## Notice: Using only the personal weather station (PWS) name. Other names are not
## working!
PWS='ISACHSEN356'
## WeatherUnderground url
## http://api.wunderground.com/api/1234567890123456/conditions/q/pws:ISACHSEN356.json
WU="http://api.wunderground.com/api/$APPID/conditions/q/pws:$PWS.json"
## middleware url
## http://yourserverip/volkszaehler.org/htdocs/middleware
URL="http://127.0.0.1/volkszaehler.org/htdocs/middleware"

##  uuid of the sensor in the volkszaehler database
UUID_temperature='12345678-1234-1234-1234-123456789012'
UUID_pressure='12345678-1234-1234-1234-123456789012'
UUID_relative_humidity='12345678-1234-1234-1234-123456789012'
UUID_dewpoint='12345678-1234-1234-1234-123456789012'
UUID_winddirection='12345678-1234-1234-1234-123456789012'
UUID_wind='12345678-1234-1234-1234-123456789012'
UUID_solar='12345678-1234-1234-1234-123456789012'
UUID_uvindex='12345678-1234-1234-1234-123456789012'

## 
## paths to binaries - you should not need to change these
CURL=/usr/bin/curl
NC=/bin/nc

weatherfile="/tmp/wunderground.$PWS"
## Get data from OpenWeatherMap
$CURL --data "" "$WU" > "$weatherfile"
test -s "$weatherfile" || exit 1 ## file is empty
## Grep the weather data from answare
## $ echo "foo start blah blah blah stop bar" | sed 's/.*start \(.*\) stop.*/\1/'
## result: blah blah blah
last_update="`grep 'observation_epoch":"' "$weatherfile"|sed -e 's/.*":"\(.*\)".*/\1/'`"
temperature="`grep '"temp_c":' "$weatherfile"|sed -e 's/.*c":\(.*\),.*/\1/'`"
pressure="`grep '"pressure_mb":"' "$weatherfile"|sed -e 's/.*mb":"\(.*\)".*/\1/'`"
relative_humidity="`grep '"relative_humidity":"' "$weatherfile"|sed -e 's/.*":"\(.*\)%.*/\1/'`"
dewpoint="`grep '"dewpoint_c":' "$weatherfile"|sed -e 's/.*c":\(.*\),.*/\1/'`"
winddirection="`grep '"wind_degrees":' "$weatherfile"|sed -e 's/.*s":\(.*\),.*/\1/'`"
wind="`grep '"wind_kph":' "$weatherfile"|sed -e 's/.*kph":\(.*\),.*/\1/'`"
solar="`grep '"solarradiation":"' "$weatherfile"|sed -e 's/.*on":"\(.*\)".*/\1/'`"
uvindex="`grep '"UV":"' "$weatherfile"|sed -e 's/.*UV":"\(.*\)",".*/\1/'`"

## Convert the actual computer time in a timestamp
dateA=`date +%Y-%m-%d`
timeA=`date +%H:%M:%S`
## Timestamp actual computer time
## timestamp="`date -d "$timeA" "+%s000"`"
## Timestamp from OpenWeatherMap
timestamp=$last_update"000"

### Debug
 echo -e "LocalTime: "`date`
 echo -e "UTC Time: "`date -u`
 echo "PWS: "$PWS
 echo "Temp: "$temperature"°C"
 echo "Humi: "$relative_humidity"%"
 echo "Pres: "$pressure"hPa"
 echo "Dewp: "$dewpoint"°C"
 echo "WDir: "$winddirection"°"
 echo "Wind: "$wind"km/h"
 echo "Solar: "$solar"W/m2"
 echo "UV: "$uvindex""
 echo "Date: "$dateA $timeA
 echo "Last Update: "$last_update
 echo "Timestamp: "$timestamp
### Debug

# With timestemp from weather service
# $CURL --data "" "$URL/data/$UUID_temperature.json?ts=$timestamp&value=$temperature" &>/dev/null
# $CURL --data "" "$URL/data/$UUID_pressure.json?ts=$timestamp&value=$pressure" &>/dev/null
# $CURL --data "" "$URL/data/$UUID_relative_humidity.json?ts=$timestamp&value=$relative_humidity" &>/dev/null
# $CURL --data "" "$URL/data/$UUID_dewpoint.json?ts=$timestamp&value=$dewpoint" &>/dev/null
# $CURL --data "" "$URL/data/$UUID_winddirection.json?ts=$timestamp&value=$winddirection" &>/dev/null
# $CURL --data "" "$URL/data/$UUID_wind.json?ts=$timestamp&value=$wind" &>/dev/null
# $CURL --data "" "$URL/data/$UUID_solar.json?ts=$timestamp&value=$solar" &>/dev/null
# $CURL --data "" "$URL/data/$UUID_uvindex.json?ts=$timestamp&value=$uvindex" &>/dev/null

# Withou timestamp, using local PC time
$CURL --data "" "$URL/data/$UUID_temperature.json?value=$temperature" &>/dev/null
$CURL --data "" "$URL/data/$UUID_pressure.json?value=$pressure" &>/dev/null
$CURL --data "" "$URL/data/$UUID_relative_humidity.json?value=$relative_humidity" &>/dev/null
$CURL --data "" "$URL/data/$UUID_dewpoint.json?value=$dewpoint" &>/dev/null
$CURL --data "" "$URL/data/$UUID_winddirection.json?value=$winddirection" &>/dev/null
$CURL --data "" "$URL/data/$UUID_wind.json?value=$wind" &>/dev/null
$CURL --data "" "$URL/data/$UUID_solar.json?value=$solar" &>/dev/null
$CURL --data "" "$URL/data/$UUID_uvindex.json?value=$uvindex" &>/dev/null

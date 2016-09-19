#!/bin/bash 
## @outhor Norbert Walter <norbert-walter@web.de>
## Cron: 39  *	* * *		~/log_weather_wu.sh >> log.weather_wu
## This is a service from www.wunderground.com
## Please install on Ubuntu the package curl before using the script
## This script get accurate weather data directly from personal weather stations
## in your environment. World wide are more than 200.000 stations active.
## Info: Not all stations send all weather data. Please check the station on
## the webpage before using.

## WeatherUnderground Application Key
## Create a free user account on www.wunderground.com and use the free application key
## Limitations! max 500 requests per day and max 10 reqiests per minute
## Save interval must grater than 5 min otherwise stop the service!
## eg. APPID='1234567890123456'
APPID=''

## Personal weater station location name
## The station name you find on the webpage www.wunderground.com
## Notice: Using only the personal weather station (PWS) name. Other names are not
## working!
PWS='ISACHSEN356'

## Selection time source for saving in database
## 0 = using local PC time
## 1 = using update timestamp from WeatherUnderground
TS='1'

## middleware url
## http://yourserverip/volkszaehler.org/htdocs/middleware
URL="http://127.0.0.1/volkszaehler.org/htdocs/middleware"

## UUID of the sensor in the volkszaehler database
## Is the UUID empty then dont saving in the VZ database
## eg. UUID_temperature='12345678-1234-1234-1234-12345678901a' 
UUID_temperature='12345678-1234-1234-1234-12345678901a'
UUID_pressure='12345678-1234-1234-1234-12345678901b'
UUID_relative_humidity='12345678-1234-1234-1234-12345678901c'
UUID_dewpoint='12345678-1234-1234-1234-12345678901d'
UUID_winddirection='12345678-1234-1234-1234-12345678901e'
UUID_wind='12345678-1234-1234-1234-12345678901f'
UUID_solar='12345678-1234-1234-1234-12345678901g'
UUID_uvindex='12345678-1234-1234-1234-12345678901h'


#### Dont edit the following lines! ####

## WeatherUnderground url
## http://api.wunderground.com/api/1b0d33c840c54ac4/conditions/q/pws:ISACHSEN356.json
WU="http://api.wunderground.com/api/$APPID/conditions/q/pws:$PWS.json"
 
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
 echo "Friendly weather service from WeatherUnderground (C)"
 echo "www.wunderground.com"
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


# Save the data with or withou timestamp from WeatherUnderground
# when exist a UUID
if [ -n "$APPID" ]; then
  if [ $TS = "0" ]; then
  echo "Saving all values with local PC time"
  timestring=""
  else
  echo "Saving all values with update time from WeatherUnderground"
  timestring="ts="$timestamp"&"
  fi
  if [ -n "$UUID_temperature" ]; then
  echo "Save Temp with UUID: "$UUID_temperature" in database"
  $CURL --data "" "$URL/data/$UUID_temperature.json?$timestring""value=$temperature" &>/dev/null
  fi
  if [ -n "$UUID_pressure" ]; then
  echo "Save Pressure with UUID: "$UUID_pressure" in database"
  $CURL --data "" "$URL/data/$UUID_pressure.json?$timestring""value=$pressure" &>/dev/null
  fi
  if [ -n "$UUID_relative_humidity" ]; then
  echo "Save Humidity  with UUID: "$UUID_relative_humidity" in database"
  $CURL --data "" "$URL/data/$UUID_relative_humidity.json?$timestring""value=$relative_humidity" &>/dev/null
  fi
  if [ -n "$UUID_dewpoint" ]; then
  echo "Save Dewpoint with UUID: "$UUID_dewpoint" in database"
  $CURL --data "" "$URL/data/$UUID_dewpoint.json?$timestring""value=$dewpoint" &>/dev/null
  fi
  if [ -n "$UUID_winddirection" ]; then
  echo "Save Winddirection with UUID: "$UUID_winddirection" in database"
  $CURL --data "" "$URL/data/$UUID_winddirection.json?$timestring""value=$winddirection" &>/dev/null
  fi
  if [ -n "$UUID_wind" ]; then
  echo "Save Windspeed with UUID: "$UUID_wind" in database"
  $CURL --data "" "$URL/data/$UUID_wind.json?$timestring""value=$wind" &>/dev/null
  fi
  if [ -n "$UUID_solar" ]; then
  echo "Save Solar with UUID: "$UUID_solar" in database"
  $CURL --data "" "$URL/data/$UUID_solar.json?$timestring""value=$solar" &>/dev/null
  fi
  if [ -n "$UUID_uvindex" ]; then
  echo "Save UV-Index with UUID: "$UUID_uvindex" in database"
  $CURL --data "" "$URL/data/$UUID_uvindex.json?$timestring""value=$uvindex" &>/dev/null
  fi
else
echo "APPID is empty! Please set the Application Key"
fi

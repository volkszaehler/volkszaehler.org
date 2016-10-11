
#!/bin/bash
## @outhor Norbert Walter <norbert-walter@web.de>
## Cron: 39  *  * * *           ~/log_weather_wu.sh >> log.weather_wu
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

if [ ! -n "$APPID" ]; then
        echo "APPID is empty! Please set the Application Key"
        exit 1
fi


## Personal weater station location name
## The station name you find on the webpage www.wunderground.com
## Notice: Using only the personal weather station (PWS) name. Other names are not
## working!
PWS='IVCKLABR5'


## Selection time source for saving in database
## 0 = using local PC time
## 1 = using update timestamp from WeatherUnderground
TS='1'

## middleware url
URL="http://127.0.0.1:80/middleware.php"

## UUIDs of the sensor in the volkszaehler database
## If UUID is empty, data won't be persisted.
UUID_temperature=''
UUID_pressure=''
UUID_relative_humidity=''
UUID_dewpoint=''
UUID_winddirection=''
UUID_wind=''
UUID_solar=''
UUID_uvindex=''


#### Dont edit the following lines! ####

## Get data from OpenWeatherMap
weatherfile="$(curl --data "" -s "http://api.wunderground.com/api/$APPID/conditions/q/pws:$PWS.json")"


if [ $(grep -c "keynotfound" <<< "$weatherfile" ) -ge "1" ];then
        echo "wrong API key"
        exit 1
fi


## Grep the weather data from answare
## $ echo "foo start blah blah blah stop bar" | sed 's/.*start \(.*\) stop.*/\1/'
## result: blah blah blah
last_update="`grep 'observation_epoch":"' <<< "$weatherfile"|sed -e 's/.*":"\(.*\)".*/\1/'`"
temperature="`grep '"temp_c":' <<< "$weatherfile"|sed -e 's/.*c":\(.*\),.*/\1/'`"
pressure="`grep '"pressure_mb"' <<< "$weatherfile"|sed -e 's/.*mb":"\(.*\)".*/\1/'`"
relative_humidity="`grep '"relative_humidity"' <<< "$weatherfile"|sed -e 's/.*":"\(.*\)%.*/\1/'`"
dewpoint="`grep '"dewpoint_c":' <<< "$weatherfile"|sed -e 's/.*c":\(.*\),.*/\1/'`"
winddirection="`grep '"wind_degrees":' <<< "$weatherfile"|sed -e 's/.*s":\(.*\),.*/\1/'`"
wind="`grep '"wind_kph":' <<< "$weatherfile"|sed -e 's/.*kph":\(.*\),.*/\1/'`"
solar="`grep '"solarradiation"' <<< "$weatherfile"|sed -e 's/.*on":"\(.*\)".*/\1/'`"
uvindex="`grep '"UV"' <<< "$weatherfile"|sed -e 's/.*UV":"\(.*\)",".*/\1/'`"

## Timestamp actual computer time
## timestamp="`date -d "$timeA" "+%s000"`"
## Timestamp from OpenWeatherMap
timestamp="$last_update"000

### Debug
echo "Friendly weather service from WeatherUnderground (C)"
echo "www.wunderground.com"
echo -e "LocalTime: "`date`
echo -e "UTC Time: "`date -u`
echo "PWS: $PWS"
echo "Temp: $temperature°C"
echo "Humi: $relative_humidity%"
echo "Pres: $pressure hPa"
echo "Dewp: $dewpoint°C"
echo "WDir: $winddirection°"
echo "Wind: $wind km/h"
echo "Solar: $solarW/m2"
echo "UV: $uvindex"
echo "Date: $(date +%Y-%m-%d\ %H:%M:%S)"
echo "Last Update: $last_update"
echo "Timestamp: $timestamp"
### Debug


# Save the data with or withou timestamp from WeatherUnderground
# when exist a UUID

if [ "$TS" = "0" ]; then
        echo "Saving all values with local PC time"
        timestring="$(date +%s)"000
else
        echo "Saving all values with update time from WeatherUnderground"
        timestring="$timestamp"
fi


push (){

curl --data "" -s "$URL/data/$1.json?ts=$2&value=$3"
        }



for i in "temperature" "pressure" "relative_humidity" "dewpoint" "winddirection" "wind" "solar" "uvindex";do
        UUID="$(eval echo \$UUID_$i)"
        value="$(eval echo \$$i)"

        if [ -n "$UUID" ];then
                echo "Save $i with UUID: $UUID in database"
                push "$UUID" "$timestring" "$value"
        fi

done


exit

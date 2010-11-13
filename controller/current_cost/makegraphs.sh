#!/bin/bash
set -x

export LC_ALL=de_DE.UTF-8

export param1='--end now --width 880 --height 300 --slope-mode --vertical-label VoltAmpere --lower-limit 0 --alt-autoscale-max'

export param2='DEF:Power1=powertemp.rrd:Power1:AVERAGE 
	       DEF:Power2=powertemp.rrd:Power2:AVERAGE 
	       DEF:Power3=powertemp.rrd:Power3:AVERAGE
               CDEF:Ln1=Power1,Power1,UNKN,IF 
	       CDEF:Ln2=Power1,Power1,Power2,+,UNKN,IF 
	       CDEF:Ln3=Power3,Power1,Power2,Power3,+,+,UNKN,IF
               AREA:Power1#A0A0FF:Phase1 
	       AREA:Power2#A0FFA0:Phase2:STACK 
	       AREA:Power3#FFA0A0:Phase3\j:STACK
               LINE1:Ln3#AA0000 
	       LINE1:Ln2#00AA00 
	       LINE1:Ln1#0000AA'

export param3='GPRINT:Power1:LAST:%2.1lf%sVA 
	       GPRINT:Power2:LAST:%2.1lf%sVA 
	       GPRINT:Power3:LAST:%2.1lf%sVA\j'

export basepath='/path/to/your/webserver/htdocs/asubdir'

get-ts () {
 export ts=`date +"Graph vom %A, %d.%m.%Y um %H:%M Uhr"`
}

mvimg () {
 rm $basepath/$1
 mv $basepath/tmp.png $basepath/$1
}

mkimg () {
 if [ -e "$1.do" ];then
  rm $1.do
  get-ts
  title="Energieverbrauch $2 - $ts"  
  if [ $1 == "15m" ];then
    rrdtool graph $basepath/tmp.png --title "$title" --start end-$1 $param1 $param2 $param3
   else
    rrdtool graph $basepath/tmp.png --title "$title" --start end-$1 $param1 $param2
  fi
  mvimg power-$1.png
 fi 
}

while true;do 
 mkimg 15m "letzte 15 Minuten"
 mkimg 1h  "letzte Stunde"
 mkimg 12h "letzte 12 Stunden"
 mkimg 1d  "letzter Tag" 
 mkimg 1w  "letzte Woche"
 mkimg 1m  "letzter Monat"
 mkimg 3m  "letztes Quartal"
 mkimg 1y  "letztes Jahr"
sleep 10;
done

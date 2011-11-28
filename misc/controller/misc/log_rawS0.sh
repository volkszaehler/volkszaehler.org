#!/bin/bash
#
# This is a simple bash script to log S0-Hutschienenzähler, directly connected to an RS232 port
#
# @copyright Copyright (c) 2011, The volkszaehler.org project
# @package controller
# @license http://www.opensource.org/licenses/gpl-license.php GNU Public License
# @author Harald Koenig <koenig@tat.physik.uni-tuebingen.de>
#
##
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

stty 110 time 1 min 1 -icanon < /dev/ttyUSB0

 #( strace -ttt -e read cat  < /dev/ttyUSB0 > /dev/null ) 2>&1 | awk '/read\(0, "\\0", 4096\)   = 1/{ t=int($1 + 0.5);  system("echo wget -O-  \"http://harald/f10/volkszaehler.org/httplog.php?uuid=b4d1326b-bf98-431c-802b-3e1864bcc001&port=USB-S-1&time=\"" t " # >& /dev/null"); }'

# ( strace -ttt -e write cat  < /dev/ttyUSB0 > /dev/null ) 2>&1 | awk 'NR==1{t0=$1}/write\(1, "\\0", 1\)  *= 1/{ t=$1; print 3600e3 /(t-t1)/2000 ,  t-t0,t-t1,$0;t1=t }'

( strace -ttt -e write cat  < /dev/ttyUSB0 > /dev/null ) 2>&1 | awk '/write\(1, "\\[0-7]*", 1\)  *= 1/{ t=($1 + 0);  system("set -x ; wget -O-  \"http://harald/volkszaehler.org/htdocs/middleware/data/d47c00f0-fa34-11df-af19-8fbbf6a5aa4b.json?operation=add&value=1&ts=\"" int(t*1000.0) " # >& /dev/null"); }'

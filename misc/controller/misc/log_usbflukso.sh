#!/bin/bash
#
# This is a simple bash script to log data from a fluksousb adapter
#
# @link http://developer.mysmartgrid.de/doku.php?id=fluksousb
# @author Harald Koenig <koenig@tat.physik.uni-tuebingen.de>
# @copyright Copyright (c) 2011-2018, The volkszaehler.org project
# @license https://opensource.org/licenses/GPL-3.0 GNU General Public License version 3
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

stty 4800  < /dev/ttyUSB1

while read pwr r ; do
    [[ "$pwr" == *pwr* ]] || continue
    [[ "$r"   == 0111156789abcdef0123456789abcde0* ]] || continue
    p="${r#0111156789abcdef0123456789abcde0:}"

    wget -O-  "http://volkszaehler.org/demo/middleware/data/80ec28a0-6881-11e0-9d05-653120632357.json?operation=add&value=$p"
done < /dev/ttyUSB1


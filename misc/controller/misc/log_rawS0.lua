#!/usr/bin/lua
--
-- This is a lua bash script to log S0-Hutschienenzähler, directly connected to an RS232 port
--
-- @author Harald Koenig <koenig@tat.physik.uni-tuebingen.de>
-- @copyright Copyright (c) 2011-2017, The volkszaehler.org project
-- @license https://opensource.org/licenses/gpl-license.php GNU Public License
--
---
-- This file is part of volkzaehler.org
--
-- volkzaehler.org is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- any later version.
--
-- volkzaehler.org is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with volkszaehler.org. If not, see <http://www.gnu.org/licenses/>.
---

require "vz_conf"

url_fmt = "http://volkszaehler.org/demo/middleware/data/%s.json?ts=%s&value=1"

uuids = {
      U = "434c8580-dafa-11df-b69a-c7234ef37399" ,
      A = "56fafaa0-dafa-11df-ae15-199b2a8a5b54" ,
      B = "de672180-c231-11df-a546-eda234e5b7dd" ,
      C = "de653180-c631-11df-a546-edadbae5b7dd" ,
      D = "df223480-c631-11df-a546-edadb23427dd" ,
      default = "00000000-0000-0000-0000-000000000000"
}

ttys = {   "/dev/ttyACM0" ,  "/dev/ttyUSB0", "/dev/ttyS0" , "/dev/tty" ,  }

require "socket"
http = require("socket.http")

-- print (string.format("%f",socket.gettime()))

-- test resolution of gettime()...
t1 = socket.gettime()
t2 = socket.gettime()

if ( (t1-math.floor(t1)) + (t2-math.floor(t2)) == 0) then
  -- gettime() not usable ...
  require "libtime"

  function time_get()
    return { libtime.gettimeofday() }
  end

  function time_diff(t1,t2)
    return ( t1[1] - t2[1] + (t1[2] - t2[2]) * 1e-6)
  end

  function time_sec(t)
    return t[1]
  end

  function time_str(t)
    return string.format("%d.%06d", t[1],t[2])
  end

  function time_mstr(t)
    return string.format("%d%03d", t[1],t[2]/1000)
  end

else

  function time_get()
    return socket.gettime()
  end

  function time_diff(t1,t2)
    return t1-t2
  end

  function time_sec(t)
    return math.floor(t + 0.5)
  end

  function time_str(t)
    return string.format("%1.6f", t)
  end

  function time_mstr(t)
    return string.format("%1.0f", t * 1000)
  end

end


-- find and init serial line...
baud = 50

for k,v in pairs( ttys ) do
    tty = v
    print (tty)
    rserial = io.open(tty,"r")
    if not (rserial == nil) then break ; end
end

os.execute("baud=" .. baud .. " ; stty -a < " .. tty .. " | grep -q $baud.baud || stty " .. baud .. " time 1 min 1 -icanon -echo < " .. tty )


-- now let's start processing the input...
t0 = time_get()
t1 = t0
while true do
        inchar = rserial:read(1)
	t = time_get()
	if not (string.match(inchar, "[a-z]")) then
	   if uuids[inchar] then
	      uuid =  uuids[inchar]
	   else
	      uuid = uuids.default;
	   end
	   url = string.format(url_fmt, uuid, time_mstr(t))
	   dt0 = time_diff(t,t0)
	   dt1 = time_diff(t,t1)
	   p = 3600e3 / dt1 / 2000.
	   print (time_str(t), string.format("%10.6f", dt0), string.format("%10.6f", dt1), string.format("%10.6f", p), url)
	   http.request(url)
	   t1 = t
	end
end

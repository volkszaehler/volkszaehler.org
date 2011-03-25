<?php
# @copyright Copyright (c) 2010, The volkszaehler.org project
# @package frontend
# @license http://www.opensource.org/licenses/gpl-license.php GNU Public License
# @author Sven Peitz <sven@pubeam.de>
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

#Daten vom Eingabeform uebernehmen und Variablen zuordnen
  $serverip = $_POST["serverip"];
  $title = $_POST["title"];
  $resolution = $_POST["resolution"];
  $description = $_POST["description"];

#Link anschliesend aufrufen
header("Location: http://$serverip/middleware/channel.json?operation=add&title=$title&type=power&resolution=$resolution&description=$description");

#Einige Debug hilfen
#echo "http://";
#echo $serverip;
#echo "/middleware/channel.json?operation=add&title=";
#echo $title;
#echo "&type=power&resolution=";
#echo $resolution;
#echo "&description=";
#echo $description;


?>
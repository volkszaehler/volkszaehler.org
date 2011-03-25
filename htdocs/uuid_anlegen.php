<?# @copyright Copyright (c) 2010, The volkszaehler.org project
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
?>
 
<form method="POST" action="query.php">
Einfaches Formular zum erstellen einer UUID in der SQL Datenbank
<br>
<br>
ServerIP mit pfad zum genutzten volkszaehler (server) angeben<br>
z.B http://192.168.0.1:/zaehler oder http://192.168.0.1 oder http://www.vokszaehler.org<br>
Es kann auch https verwendet werden https://192.168.0.1 <br>
 <input type="text" size="17" name="serverip"><br>
<br>
  Beschreibung angeben z.B. Kuehlschrank <br>
  <input type="text" size="17" name="title"><br>
<br>
  Aufloesung des Zaehlers angeben z.B. 1000<br>
  <input type="text" size="17" name="resolution"><br>
<br>
  Hersteller des Zaehlers z.B. Swissnox<br>
  <input type="text" size="17" name="description"><br>
<br>
  <INPUT type="submit" value="Send"> <INPUT type="reset"><br>
   Es wird eine UUID erstellt und dann die json Antwort zurueckgesendet in der sich die UUID befindet.<br>
   diese muss dann im Controller angegeben werden z.B. Netio	
 </form>
 <br>
                  
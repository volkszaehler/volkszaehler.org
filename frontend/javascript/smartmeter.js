/*
* Copyright (c) 2010 by Florian Ziegler <fz@f10-home.de>
* 
* This program is free software; you can redistribute it and/or modify it
* under the terms of the GNU General Public License (either version 2 or
* version 3) as published by the Free Software Foundation.
*     
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*     
* You should have received a copy of the GNU General Public License
* along with this program; if not, write to the Free Software
* Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
*     
* For more information on the GPL, please go to:
* http://www.gnu.org/copyleft/gpl.html
*/  

// return an array with timestamps of e.g. 
// 2010-05-01 00:00:00, 2010-05-01 01:00, 2010-05-01 02:00 for grouping=hour
// between windowStart and windowEnd of json response
function getEmptyGroupArray() {
	var empty_array = new Object();
	
	var iterator = getGroupedTimestamp(myWindowStart);
	//$('#debug').empty().append('start:'+myWindowStart+'end:'+myWindowEnd);
	
	if(myWindowStart < myWindowEnd && iterator < myWindowEnd) {
		var i=0;
		while(iterator < myWindowEnd) {
			i++;
			
			empty_array[iterator] = 0;
			
			var iteratorDate = new Date(iterator);
			//$('#debug').append('#'+i+':'+iteratorDate+'<br>');
			iteratorDate.setDate(iteratorDate.getDate()+1);
			// very bad bug: infinity loop for summer winter change
			if(i==750) return empty_array;
			
			iterator = iteratorDate.getTime();
		}
	}
	
	return empty_array
}

function calcMyWindowStart() {
	var myWindowStart = new Date(myWindowEnd);
	
	var year = myWindowStart.getFullYear();
	var month = myWindowStart.getMonth();// 0 is january
	var day = myWindowStart.getDate();	// getDay() returns day of week
	var hours = myWindowStart.getHours();
	var minutes = myWindowStart.getMinutes();
	
	//var windowSize = f.window.value.substring(0,1);
	var windowSize = "1";
	//var windowInterval = f.window.value.substring(1);
	var windowInterval = "MONTH"; // we want to display 1 day (for now)
	
	myWindowStart.setMonth(myWindowStart.getMonth()-windowSize);
	return myWindowStart.getTime();
}

// groups a timestamp depending on grouping value
// e.g. 2010-05-01 23:23:23 will become 2010-05-01 23:00:00 vor grouping=hour
function getGroupedTimestamp(timestamp) {
	time = new Date(timestamp);
		
	var year = time.getFullYear();
	var month = time.getMonth();// 0 is january
	var day = time.getDate();	// getDay() returns day of week
	var hours = time.getHours();
	var minutes = time.getMinutes();
	
			hours = 0;
	
	return (new Date(year,month,day,hours,minutes)).getTime();
}


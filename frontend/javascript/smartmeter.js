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
	
	if(myWindowStart < myWindowEnd && iterator < myWindowEnd) {
		
		
		while(iterator < myWindowEnd) {
			
			empty_array[iterator] = 0;
			
			var iteratorDate = new Date(iterator);
			
			switch(f.grouping.value) {
				case 'year':
					iteratorDate.setYear(iteratorDate.getYear()+1);
					break;
				case 'month':
					iteratorDate.setMonth(iteratorDate.getMonth()+1);
					break;
				case 'day':
					iteratorDate.setDate(iteratorDate.getDate()+1);
					break;
				case 'hour':
					iteratorDate.setHours(iteratorDate.getHours()+1);
					break;
				case 'minute':
					iteratorDate.setMinutes(iteratorDate.getMinutes()+1);
					break;
				default:
					return empty_array;
			}
			
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
	
	var windowSize = f.window.value.substring(0,1);
	var windowInterval = f.window.value.substring(1);
	
	switch(windowInterval) {
		case 'YEAR':
			myWindowStart.setYear(myWindowStart.getFullYear()-windowSize);
			break;
		case 'MONTH':
			myWindowStart.setMonth(myWindowStart.getMonth()-windowSize);
			break;
		case 'DAY':
			myWindowStart.setDate(myWindowStart.getDate()-windowSize);
			break;
		case 'HOUR':
			myWindowStart.setHours(myWindowStart.getHours()-windowSize);
			break;
		case 'MINUTE':
			myWindowStart.setMinutes(myWindowStart.getMinutes()-windowSize);
			break;
	}
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
	
	// hint: its correct without break;
	switch(f.grouping.value) {
		case 'year':
			month = 1;
		case 'month':
			day = 1;
		case 'day':
			hours = 0;
		case 'hour':
			minutes = 0;
	}
	
	return (new Date(year,month,day,hours,minutes)).getTime();
}


function raw2Energy(raw) {
	var data_grouped_time = getEmptyGroupArray();
	var data_grouped = new Array();
	alert('e')
	return;
	if(f.grouping.value == '')
		return;
		
	if(raw.data.length == 0) {
		return [[0,0]];
	}
	
	// for each timestamp in json response
	for(var i=0;i<raw.data.length;i++) {
		data_grouped_time[raw.data[i][0]] = raw.data[i][1];
	}
	t = 1;
	switch(f.grouping.value) {
		case 'year':
			t *= 365;
		case 'day':
			t *= 24;
		case 'hour':
			t *= 60;
		case 'minute':
			t *= 60;
	}
	
	// transform to proper array and energy instead of pulse count
	for(var timestamp in data_grouped_time) {
		
		if(f.info.value == 'energy')
			data_grouped.push(data_grouped_time[timestamp]/raw.resolution);
		else {
			data_grouped.push([timestamp*1, 3600 * 1000 * data_grouped_time[timestamp] / raw.resolution / t ]);
		}
	}
	
	if(f.info.value == 'power') {
		for(var i=data_grouped.length-1;i>=0;i--) {
			if(data_grouped[i][1]==0 && i>0 && i<data_grouped.length-1 && data_grouped[i][0] > raw.data[0][0]*1000)
				data_grouped[i][1] = data_grouped[i+1][1];
		}
	}
	
	return data_grouped;
}


function raw2Power(raw,moving_average) {
	
	var last_timestamp = 0;
	var last_power = 0;
	var power = 0;
	var data_line = new Array();
	
	if(typeof raw == 'undefined' || raw.data.length == 0) {
		return [[0,0]];
	}
	
	// for each timestamp in json response
	for(var i=0;i<raw.data.length;i++) {
		if(last_timestamp>0) {
			
			// difference between this and last timestamp
			difference = raw.data[i][0] - last_timestamp;
			
			// power = 3600*1000/difference/resolution*count
			power = Math.round(3600 * 1000/difference/raw.resolution*raw.data[i][1]);
			
			// average with last power value
			if(moving_average && last_power>0 && Math.abs(last_power-power)<0.25*power)
				power = (power +  last_power)/2;
			
			// additional value for last_power > power*1.25
			if(last_power > power*1.25 && last_timestamp) {
				data_line.push([last_timestamp,power]);
			}
			
			// array with timestamp and power
			data_line.push([raw.data[i][0],power]);
		}
		
		last_timestamp = raw.data[i][0];
		last_power = power;
	}
	
	// return array with power@timestamps
	return data_line;
}
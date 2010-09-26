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

function loadChannelList() {
	$('#debug').append('<a href="../backend/index.php/data/"' + myUUID + '"/channel">json</a>');
	// load json data
	$.getJSON("../backend/index.php/data/" + myUUID + {format: 'json'});
	
}

function autoReload() {
	// call getData if autoReload checkbox is active
	if(f.autoReload.checked == true) {
		myWindowEnd = getGroupedTimestamp((new Date()).getTime());
		getData();
	}
}

function moveWindow(mode) {
	if(mode == 'last')
		myWindowEnd = (new Date()).getTime();
	if(mode == 'back') {
		myWindowEnd = myWindowStart;
	}
	if(mode == 'forward') {
		myWindowEnd += (myWindowEnd-myWindowStart);
	}
	
	getData();
}


function getData() {
	/*
	 * if(f.ids.length>0) $('#loading').empty().html('<img
	 * src="images/ladebild.gif" />');
	 *  // list of channel ids, comma separated ids_parameter = "";
	 * 
	 * if(typeof f.ids.length == 'undefined') { // only one channel
	 * ids_parameter = f.ids.value; } else { // more than one channel for(i=0;i<f.ids.length;i++) {
	 * if(f.ids[i].checked == 1) { ids_parameter += f.ids[i].value + ","; } } }
	 */
	
	// calcMyWindowStart
	myWindowStart = calcMyWindowStart();
	
	$('#debug').append('<a href="../backend/index.php/data/'+myUUID+'/format/json/from/'+myWindowStart+'/to/'+myWindowEnd+'">json</a>');
	// load json data with given time window
	// $.getJSON("../backend/index.php/data/" + myUUID +
	// '/format/json/from/'+myWindowStart+'/to/'+myWindowEnd, function(j){
	$.getJSON("../backend/index.php/data/" + myUUID + '.json?from='+myWindowStart+'&to='+myWindowEnd, function(j){
		data = j;
		$('#debug').empty().append(data.toSource());
		// then show/reload the chart
		// if(data.channels.length > 0 && data.channels[0].pulses.length > 0)
			showChart();
		$('#loading').empty();
	});
	
	return false;
}

function showChart() {
	var jqData = new Array();
	var series_chart = new Array();
	
	$('#ChartInfo').hide();
	$('#ChartPlot').show();
	
	jqOptions = {
			legend:{show:true},
			series:[],
			cursor:{zoom:true, showTooltip:true,constrainZoomTo:'x'},
			seriesDefaults:{lineWidth:1,showMarker:false}}
	
	// stack plot seiries if add channels is active
	if(f.stackChannels.checked == true) {
		jqOptions.stackSeries = true;
		jqOptions.seriesDefaults.fill = true;
		jqOptions.seriesDefaults.showShadow = false;
	}
	
	// legend entries
	for( uuid in data.channels ) { 
		jqOptions.series.push({label:data.channels[uuid]['description']}); 
	}
	
	EformatString = '%d.%m.%y %H:%M';
	
	// power (moving average) gray line
	for( uuid in data.data ) { 
		jqData.push(data.data[uuid]); 
	}



	jqOptions.axes = {
			yaxis:{autoscale:true, min:0, label:"Leistung (Watt)", tickOptions:{formatString:'%.3f'},labelRenderer: $.jqplot.CanvasAxisLabelRenderer},
			xaxis:{autoscale:true, min:calcMyWindowStart(), max:myWindowEnd, tickOptions:{formatString:EformatString,angle:-30},pad:1, renderer:$.jqplot.DateAxisRenderer,rendererOptions:{tickRenderer:$.jqplot.CanvasAxisTickRenderer}},
	};
	
	chart = $.jqplot("ChartDIV",jqData,jqOptions);
	chart.replot();
	
}

function generateAxisTicks() {
	var data_grouped_time = getEmptyGroupArray();
	var ticks = new Array();
	
	for(var timestamp in data_grouped_time) {
		var time = new Date(timestamp*1000);
		ticks.push(time.getDate()+'.'+(time.getMonth()+1)+'.'+' '+time.getHours()+':00');
	}
	
	return ticks;
}

function updateTips(t) {
	tips
		.text(t)
		.addClass('ui-state-highlight');
	setTimeout(function() {
		tips.removeClass('ui-state-highlight', 1500);
	}, 500);
}


function checkLength(o,n,min,max) {
	
	if ( o.val().length > max || o.val().length < min ) {
		o.addClass('ui-state-error');
		updateTips("Length of " + n + " must be between "+min+" and "+max+".");
		return false;
	} else {
		return true;
	}
}

function checkRegexp(o,regexp,n) {
	if ( !( regexp.test( o.val() ) ) ) {
		o.addClass('ui-state-error');
		updateTips(n);
		return false;
	} else {
		return true;
	}
}
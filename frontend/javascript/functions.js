/**
 * Javascript functions for the frontend
 *
 * @author Florian Ziegler <fz@f10-home.de>
 * @author Justin Otherguy <justin@justinotherguy.org>
 * @author Steffen Vogel <info@steffenvogel.de>
 * @copyright Copyright (c) 2010, The volkszaehler.org project
 * @package default
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */
/*
 * This file is part of volkzaehler.org
 *
 * volkzaehler.org is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * volkzaehler.org is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with volkszaehler.org. If not, see <http://www.gnu.org/licenses/>.
 */

function calcMyWindowStart() {
	var myWindowStart = new Date(myWindowEnd);
	
	var year = myWindowStart.getFullYear();
	var month = myWindowStart.getMonth();// 0 is january
	var day = myWindowStart.getDate();	// getDay() returns day of week
	var hours = myWindowStart.getHours();
	var minutes = myWindowStart.getMinutes();
	
	// var windowSize = f.window.value.substring(0,1);
	var windowSize = "1";
	// var windowInterval = f.window.value.substring(1);
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
	$('#debug').append('<a href="../backend/index.php/data/"' + myUUID + '"/channel">json</a><br />');
	// load json data
	$.getJSON("../backend/index.php/data/" + myUUID + {format: 'json'});
	
}

function autoReload() {
	if (false) {
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
	
	$('#debug').append('<a href="../backend/index.php/data/' + myUUID + '.json?from='+myWindowStart+'&to='+myWindowEnd+'&resolution=500">JSON</a><br />');
	// load json data with given time window
	// $.getJSON("../backend/index.php/data/" + myUUID +
	// '/format/json/from/'+myWindowStart+'/to/'+myWindowEnd, function(j){
	$.getJSON("../backend/index.php/data/" + myUUID + '.json?from='+myWindowStart+'&to='+myWindowEnd+'&resolution=500', function(j){
		data = j;
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

/*
 * jQuery extensions
 */

$.extend({
	getUrlVars: function(){
		var vars = [], hash;
		var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
		for(var i = 0; i < hashes.length; i++) {
			hash = hashes[i].split('=');
			vars.push(hash[0]);
			vars[hash[0]] = hash[1];
		}
		return vars;
	},
	getUrlVar: function(name){
		return $.getUrlVars()[name];
	}
});